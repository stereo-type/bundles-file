<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Form\DataTransformer;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Slcorp\FileBundle\Application\Enum\FileAdapter;
use Slcorp\FileBundle\Application\Service\FileService;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 *
 */
class UniversalFileTransformer implements DataTransformerInterface
{
    /**
     * Сохраняем оригинальное значение для отслеживания изменений.
     */
    private mixed $originalValue = null;

    public function __construct(
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $component,
        private readonly string $filearea,
        private readonly int $itemid = 0,
        private readonly int $contextid = 1,
        private readonly ?int $userid = null,
        private readonly ?Closure $formDataResolver = null,
        private readonly int $maxFiles = 1,
        // Для определения типа возвращаемого значения
        private readonly ?Closure $originalValueResolver = null,
    ) {
    }

    /**
     * Преобразует значение из модели (ID файла/файлов) в значение для view.
     * Создает draft файлы, если их еще нет.
     */
    public function transform($value): mixed
    {
        // Получаем оригинальное значение из атрибута формы (если доступно)
        if ($this->originalValueResolver !== null) {
            $originalValue = ($this->originalValueResolver)();
            if ($originalValue !== null) {
                $this->originalValue = $originalValue;
            } else {
                // Если оригинальное значение не найдено, используем текущее значение
                $this->originalValue = $value;
            }
        } else {
            // Если resolver не передан, используем текущее значение
            $this->originalValue = $value;
        }

        // Если значение null или пустое - не создаем draft файл, просто возвращаем null
        // Это защита от создания draft при первом вызове с null
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            $this->logger->debug('UniversalFileTransformer::transform() - значение null/пустое, пропускаем создание draft');
            return null;
        }

        // Нормализуем значение в массив ID файлов
        $fileIds = $this->normalizeToArray($value);
        $draftItemIds = [];

        // Проверяем, не является ли значение уже draft itemid
        $isDraftValue = false;
        if (!empty($fileIds)) {
            // Проверяем первый ID - если это draft файл, значит значение уже обработано
            $firstId = reset($fileIds);
            $existingDraft = $this->entityManager->getRepository(File::class)->findOneBy([
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $firstId,
                'userid' => $this->userid,
            ]);
            if ($existingDraft instanceof File) {
                $isDraftValue = true;
                $this->logger->debug(
                    sprintf(
                        'Значение уже является draft itemid (%d), используем существующий (повторный вызов transform)',
                        $firstId
                    )
                );
                $draftItemIds = array_map('strval', $fileIds);
            }
        }

        if (!$isDraftValue) {
            if (empty($fileIds)) {
                // Файла нет - создаем пустой draft файл
                $this->logger->debug('Файла нет - создаем пустой draft файл');
                $draftItemId = $this->generateDraftItemId();
                $this->fileService->createEmptyDraftFile($draftItemId, $this->userid);
                $draftItemIds[] = (string)$draftItemId;
                $this->logger->debug(sprintf('Создан пустой draft файл с itemid: %d', $draftItemId));
            } else {
                // Есть файлы - копируем каждый в draft
                $this->logger->debug(sprintf('Есть файлы, переносим их в драфт: %d - %s', count($fileIds), json_encode($fileIds) ?: ''));
                foreach ($fileIds as $fileId) {
                    // Проверяем, есть ли уже draft файл для этого оригинального файла
                    $existingDraft = $this->entityManager->getRepository(File::class)->findOneBy([
                        'component' => 'user',
                        'filearea' => 'draft',
                        'referencefileid' => $fileId,
                        'userid' => $this->userid,
                    ]);

                    if ($existingDraft instanceof File) {
                        // Draft уже существует - используем его
                        $draftItemId = (string)$existingDraft->getItemid();
                        $draftItemIds[] = $draftItemId;
                        $this->logger->debug(
                            sprintf(
                                'Найден существующий draft для файла %d, используем itemid: %s (повторный вызов transform)',
                                $fileId,
                                $draftItemId
                            )
                        );
                    } else {
                        // Draft не найден - создаем новый
                        $draftItemId = $this->copyFileToDraft($fileId);
                        if ($draftItemId !== null) {
                            $draftItemIds[] = $draftItemId;
                            $this->logger->debug(sprintf('Скопирован файл %d в draft с itemid: %s', $fileId, $draftItemId));
                        } else {
                            $this->logger->debug('Файл не удалось переместить в драфт, создаем пустой draft файл');
                            $draftItemId = $this->generateDraftItemId();
                            $this->fileService->createEmptyDraftFile($draftItemId, $this->userid);
                            $draftItemIds[] = (string)$draftItemId;
                            $this->logger->debug(sprintf('Создан пустой draft файл с itemid: %d (файл %d не найден)', $draftItemId, $fileId));
                        }
                    }
                }
            }
        }

        // Возвращаем draft itemid (строку или массив в зависимости от maxFiles)
        return $this->maxFiles === 1 ? ($draftItemIds[0] ?? null) : $draftItemIds;
    }

    /**
     * Нормализует значение в массив ID файлов.
     * Поддерживает: массив, строку с разделителями (запятая, пробел), одиночное значение.
     */
    private function normalizeToArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter(array_map('intval', $value));
        }

        if (is_string($value)) {
            // Пробуем JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === \JSON_ERROR_NONE && is_array($decoded)) {
                return array_filter(array_map('intval', $decoded));
            }

            // Пробуем разделители (запятая, пробел, точка с запятой)
            $parts = preg_split('/[,;\s]+/', $value, -1, \PREG_SPLIT_NO_EMPTY);
            if (!$parts) {
                return [];
            }

            return array_filter(array_map('intval', $parts));
        }

        // Одиночное значение
        $intValue = is_numeric($value) ? (int)$value : null;

        return $intValue !== null ? [$intValue] : [];
    }

    /**
     * Преобразует значение из view (массив/строку ID файлов или UploadedFile) обратно в модель.
     *
     * Поддерживает два режима:
     * 1. JS загрузчики (Dropzone, etc.) - файлы загружаются через AJAX, возвращается массив draft itemid
     * 2. Стандартная загрузка - файл приходит как UploadedFile
     */
    public function reverseTransform($value): mixed
    {
        // Если пустое значение - проверяем, нужно ли удалить старый файл
        if ($value === null || $value === '') {
            // Если было значение, а теперь стало null - удаляем старый файл
            if ($this->originalValue !== null &&
                $this->originalValue !== '' &&
                $this->originalValue !== 0 &&
                $this->originalValue !== '0') {
                $this->deleteOriginalFiles();
            }

            return null;
        }

        // Нормализуем значение в массив (виджет всегда отправляет массив, даже для одного файла)
        $draftItemIds = $this->normalizeToArray($value);

        if (empty($draftItemIds)) {
            // Если было значение, а теперь стало пустым - удаляем старые файлы
            if ($this->originalValue !== null &&
                $this->originalValue !== '' &&
                $this->originalValue !== 0 &&
                $this->originalValue !== '0') {
                $this->deleteOriginalFiles();
            }

            return null;
        }

        $fileIds = [];

        foreach ($draftItemIds as $draftItemId) {
            // Проверяем - это draft файл?
            $draftFile = $this->entityManager->getRepository(File::class)->findOneBy([
                'component' => 'user',
                'filearea' => 'draft',
                'userid' => $this->userid,
                'itemid' => $draftItemId,
            ]);

            if ($draftFile instanceof File) {
                // Это draft - нужно переместить в permanent area
                $movedFileId = $this->moveFromDraftArea($draftItemId);
                if ($movedFileId !== null) {
                    $fileIds[] = $movedFileId;
                }
            } else {
                // Это обычный ID файла - возвращаем как есть
                $fileIds[] = (string)$draftItemId;
            }
        }

        // Если файлы изменились - удаляем старые файлы, которых нет в новом списке
        $newFileIds = array_map('intval', $fileIds);
        $originalFileIds = $this->normalizeToArray($this->originalValue);

        // Находим файлы, которые были удалены
        $deletedFileIds = array_diff($originalFileIds, $newFileIds);
        foreach ($deletedFileIds as $deletedFileId) {
            $this->fileService->deleteFile($deletedFileId);
        }

        // Если maxFiles = 1, возвращаем строку (первый ID) для совместимости с полями типа string
        // Если maxFiles > 1, возвращаем массив
        if ($this->maxFiles === 1) {
            return !empty($fileIds) ? $fileIds[0] : null;
        }

        // Возвращаем массив ID для множественных файлов
        return !empty($fileIds) ? $fileIds : null;
    }

    /**
     * Удаляет оригинальные файлы, которые были в поле до изменения.
     */
    private function deleteOriginalFiles(): void
    {
        if (
            $this->originalValue === null ||
            $this->originalValue === '' ||
            $this->originalValue === 0 ||
            $this->originalValue === '0') {
            return;
        }

        $fileIds = $this->normalizeToArray($this->originalValue);

        foreach ($fileIds as $fileId) {
            // Проверяем, что файл существует и относится к нужному компоненту и filearea
            $file = $this->entityManager->getRepository(File::class)->findOneBy([
                'id' => $fileId,
                'component' => $this->component,
                'filearea' => $this->filearea,
            ]);

            // Удаляем только если файл найден и соответствует параметрам
            if ($file instanceof File) {
                $this->fileService->deleteFile($fileId);
            }
        }
    }

    /**
     * Копирует файл из permanent area в draft area.
     *
     * @param int $fileId ID файла в permanent area
     * @return string|null Draft itemid или null, если файл не найден
     */
    private function copyFileToDraft(int $fileId): ?string
    {
        // Ищем файл в permanent area
        $permanentFile = $this->entityManager->getRepository(File::class)->find($fileId);

        if (!$permanentFile instanceof File) {
            // Файл не найден
            return null;
        }

        // Удаляем старые draft копии этого файла, если они есть
        $existingDrafts = $this->entityManager->getRepository(File::class)->findBy([
            'component' => 'user',
            'filearea' => 'draft',
            'referencefileid' => $fileId,
        ]);

        foreach ($existingDrafts as $existingDraft) {
            if ($existingDraft instanceof File) {
                $this->fileService->deleteFile($existingDraft->getId());
            }
        }

        // Генерируем новый draft itemid
        $draftItemId = $this->generateDraftItemId();

        // Копируем файл в draft
        $draftFile = $this->fileService->copyToDraft($fileId, $draftItemId);

        if (!$draftFile instanceof File) {
            // Не удалось скопировать - возвращаем сгенерированный draft itemid
            return (string)$draftItemId;
        }

        return (string)$draftFile->getItemid();
    }

    /**
     * Генерирует уникальный draft item ID.
     */
    private function generateDraftItemId(): int
    {
        return (int)(time() . random_int(1000, 9999));
    }

    /**
     * Перемещает файл из draft area в permanent area.
     *
     * @param int $draftItemId Draft item ID (itemid из draft area)
     * @return string|null ID перемещенного файла
     */
    private function moveFromDraftArea(int $draftItemId): ?string
    {
        try {
            // Получаем реальный itemid из данных формы (ID сущности)
            $itemid = $this->itemid;
            $contextid = $this->contextid;

            // Если установлен resolver, получаем динамические значения
            if ($this->formDataResolver !== null) {
                $formData = ($this->formDataResolver)();

                // Если itemid = 0, пытаемся получить ID из entity
                if ($itemid === 0 && is_object($formData) && method_exists($formData, 'getId')) {
                    $itemid = $formData->getId() ?? 0;
                }
            }

            // Перемещаем файл из draft в permanent
            $file = $this->fileService->moveFromDraft(
                $draftItemId,
                $this->component,
                $this->filearea,
                $itemid,
                $contextid
            );

            if (!$file instanceof File) {
                // Файл в draft не найден
                return null;
            }

            // Возвращаем ID перемещенного файла
            return $file->getId() !== null ? (string)$file->getId() : null;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            throw new TransformationFailedException(
                sprintf('Failed to move file from draft: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
