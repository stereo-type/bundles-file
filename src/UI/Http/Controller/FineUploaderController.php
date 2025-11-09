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
 * Контроллер для FineUploader UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FineUploaderController extends BaseUploadController
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

            return $this->createSuccessResponse($file);
        } catch (Throwable $e) {
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function createSuccessResponse(File $file): Response
    {
        return new JsonResponse([
            'success'     => true,
            'uuid'        => (string)$file->getId(),
            'name'        => $file->getFilename(),
            'size'        => $file->getFilesize(),
            'draftitemid' => $file->getItemid(), // itemid = draft ID когда filearea='draft'
        ]);
    }

    protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response
    {
        return new JsonResponse([
            'success' => false,
            'error'   => $message,
        ], $statusCode);
    }

    /**
     * Получает загруженный файл из запроса FineUploader.
     */
    private function getUploadedFile(Request $request): ?UploadedFile
    {
        // FineUploader отправляет файл как qqfile
        if ($request->files->has('qqfile')) {
            return $request->files->get('qqfile');
        }

        // Или как обычный file
        if ($request->files->has('file')) {
            return $request->files->get('file');
        }

        return null;
    }

    public function library(): FileUILibrary
    {
        return FileUILibrary::FINE_UPLOADER;
    }
}
