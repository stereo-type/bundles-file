<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Slcorp\FileBundle\Application\Service\FileService;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Базовый контроллер для загрузки файлов.
 * Все UI-специфичные контроллеры должны наследоваться от этого класса.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class BaseUploadController extends AbstractController implements UploaderControllerInterface
{
    public function __construct(
        protected readonly FileService $fileService,
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Обрабатывает загрузку файла.
     * Должен быть реализован в каждом UI-специфичном контроллере.
     */

    /**
     * Обрабатывает удаление файла.
     */
    #[Route(
        path: '/file/delete/{fileId}',
        name: 'slcorp_file_delete',
        requirements: ['fileId' => '\d+'],
        methods: ['DELETE', 'POST']
    )]
    public function delete(Request $request, int $fileId): JsonResponse
    {
        $file = $this->entityManager->getRepository(File::class)->find($fileId);

        if (!$file instanceof File) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }

        // TODO: Добавить проверку прав доступа
        // TODO: Удалить физический файл
        // TODO: Удалить запись из БД

        return new JsonResponse(['success' => true]);
    }

    /**
     * Получает параметры из запроса.
     */
    protected function getRequestParams(Request $request): array
    {
        return [
            'component' => $request->request->get('component', 'default'),
            'filearea' => $request->request->get('filearea', 'default'),
            'itemid' => (int) $request->request->get('itemid', 0),
            'contextid' => (int) $request->request->get('contextid', 1),
            'userid' => $request->request->get('userid') ? (int) $request->request->get('userid') : null,
        ];
    }

    /**
     * Обрабатывает загруженный файл через FileService.
     * AJAX загрузка ВСЕГДА сохраняет в draft area (filearea='draft').
     */
    protected function handleUploadedFile(UploadedFile $uploadedFile, array $params): File
    {
        // Генерируем уникальный draft item ID (как в Moodle)
        $draftItemId = $this->generateDraftItemId();

        // Сохраняем в draft area:
        // - component остается как есть (для идентификации источника)
        // - filearea = 'draft' (специальная область для черновиков)
        // - itemid = уникальный draft ID (это и есть draftitemid!)
        return $this->fileService->createFileFromUploaded(
            $uploadedFile,
            component: 'user',
            filearea: 'draft', // DRAFT AREA!
            itemid: $draftItemId, // Уникальный ID draft'а
            contextid: $params['contextid'],
            userid: $params['userid']
        );
    }

    /**
     * Генерирует уникальный ID для draft area.
     */
    protected function generateDraftItemId(): int
    {
        // Используем timestamp + random для уникальности
        return (int) (time() . rand(1000, 9999));
    }

    /**
     * Создает успешный ответ для UI библиотеки.
     */
    abstract protected function createSuccessResponse(File $file): Response;

    /**
     * Создает ответ об ошибке для UI библиотеки.
     */
    abstract protected function createErrorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): Response;
}
