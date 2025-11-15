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
 * Контроллер для Uploadify UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UploadifyController extends BaseUploadController
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
        // Uploadify ожидает простой текст "1" при успехе или JSON с данными
        // Возвращаем JSON с данными файла
        return new JsonResponse([
            'success' => true,
            'name' => $file->getFilename(),
            'size' => $file->getFilesize(),
            'url' => $this->generateUrl('slcorp_file_download', ['id' => $file->getId()]),
            'draftitemid' => $file->getItemid(), // itemid = draft ID когда filearea='draft'
        ]);
    }

    protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response
    {
        // Uploadify ожидает текст "0" при ошибке или JSON с ошибкой
        return new JsonResponse([
            'success' => false,
            'error' => $message,
        ], $statusCode);
    }

    /**
     * Получает загруженный файл из запроса Uploadify.
     */
    private function getUploadedFile(Request $request): ?UploadedFile
    {
        // Uploadify отправляет файл как 'Filedata'
        if ($request->files->has('Filedata')) {
            return $request->files->get('Filedata');
        }

        // Альтернативный вариант - 'file'
        if ($request->files->has('file')) {
            return $request->files->get('file');
        }

        return null;
    }

    public function library(): FileUILibrary
    {
        return FileUILibrary::UPLOADIFY;
    }
}

