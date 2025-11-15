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
 * Контроллер для jQuery File Upload UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class JQueryFileUploadController extends BaseUploadController
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
        // jQuery File Upload ожидает массив объектов или один объект
        $response = [
            'name' => $file->getFilename(),
            'size' => $file->getFilesize(),
            'url' => $this->generateUrl('slcorp_file_download', ['id' => $file->getId()]),
            'draftitemid' => $file->getItemid(), // itemid = draft ID когда filearea='draft'
        ];

        // Если это изображение, добавляем thumbnailUrl
        if ($file->getMimetype() && str_starts_with($file->getMimetype(), 'image/')) {
            $response['thumbnailUrl'] = $this->generateUrl('slcorp_file_download', ['id' => $file->getId()]);
        }

        // Возвращаем как массив (jQuery File Upload ожидает массив)
        return new JsonResponse([$response]);
    }

    protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response
    {
        // jQuery File Upload ожидает объект с полем error
        return new JsonResponse([
            'error' => $message,
        ], $statusCode);
    }

    /**
     * Получает загруженный файл из запроса jQuery File Upload.
     */
    private function getUploadedFile(Request $request): ?UploadedFile
    {
        // jQuery File Upload отправляет файлы как files[] (массив) или files
        if ($request->files->has('files')) {
            $files = $request->files->get('files');
            // Если это массив, берем первый файл
            if (is_array($files) && !empty($files)) {
                return $files[0];
            }
            // Если это один файл
            if ($files instanceof UploadedFile) {
                return $files;
            }
        }

        // Альтернативный вариант - files[]
        if ($request->files->has('files[]')) {
            $files = $request->files->get('files[]');
            if (is_array($files) && !empty($files)) {
                return $files[0];
            }
            if ($files instanceof UploadedFile) {
                return $files;
            }
        }

        // Или просто file
        if ($request->files->has('file')) {
            return $request->files->get('file');
        }

        return null;
    }

    public function library(): FileUILibrary
    {
        return FileUILibrary::JQUERY_FILE_UPLOAD;
    }
}
