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
 * Контроллер для Dropzone UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class DropzoneController extends BaseUploadController
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
            'id'   => $file->getId(),
            'name' => $file->getFilename(),
            'size' => $file->getFilesize(),
            'url'  => $this->generateUrl('slcorp_file_download', ['id' => $file->getId()]),
        ]);
    }

    protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response
    {
        return new JsonResponse([
            'error' => $message,
        ], $statusCode);
    }

    /**
     * Получает загруженный файл из запроса Dropzone.
     */
    private function getUploadedFile(Request $request): ?UploadedFile
    {
        // Dropzone отправляет файл как file
        if ($request->files->has('file')) {
            return $request->files->get('file');
        }

        return null;
    }

    public function library(): FileUILibrary
    {
        return FileUILibrary::DROPZONE;
    }
}
