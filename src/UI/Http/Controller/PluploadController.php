<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Контроллер для Plupload UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PluploadController extends BaseUploadController
{
    public function upload(Request $request): Response
    {
        try {
            $uploadedFile = $this->getUploadedFile($request);
            if (!$uploadedFile instanceof UploadedFile) {
                return $this->createErrorResponse('No file uploaded');
            }

            $params = $this->getRequestParams($request);
            $file = $this->handleUploadedFile($uploadedFile, $params);

            return $this->createSuccessResponse($file, $request);
        } catch (Throwable $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function createSuccessResponse(File $file, ?Request $request = null): Response
    {
        // Plupload ожидает JSON ответ в формате: {"jsonrpc" : "2.0", "result" : null, "id" : "id"}
        $id = 'id';
        if ($request) {
            $id = $request->request->get('id', 'id');
        }

        return new JsonResponse([
            'jsonrpc' => '2.0',
            'result' => [
                'name' => $file->getFilename(),
                'size' => $file->getFilesize(),
                'url' => $this->generateUrl('slcorp_file_download', ['id' => $file->getId()]),
                'draftitemid' => $file->getItemid(), // itemid = draft ID когда filearea='draft'
            ],
            'id' => $id,
        ]);
    }

    protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response
    {
        // Plupload ожидает ошибку в формате: {"jsonrpc" : "2.0", "error" : {"code": 500, "message": "..."}, "id" : "id"}
        return new JsonResponse([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $statusCode,
                'message' => $message,
            ],
            'id' => 'id',
        ], $statusCode);
    }

    /**
     * Получает загруженный файл из запроса Plupload.
     */
    private function getUploadedFile(Request $request): ?UploadedFile
    {
        // Plupload отправляет файл как 'file'
        if ($request->files->has('file')) {
            return $request->files->get('file');
        }

        return null;
    }

    public function library(): FileUILibrary
    {
        return FileUILibrary::PLUPLOAD;
    }
}
