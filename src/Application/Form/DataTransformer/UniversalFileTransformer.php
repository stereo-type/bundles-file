<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Form\DataTransformer;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
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
 */
class UniversalFileTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly FileAdapter $adapter,
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $component,
        private readonly string $filearea,
        private readonly int $itemid = 0,
        private readonly int $contextid = 1,
        private readonly ?int $userid = null,
        private readonly ?Closure $formDataResolver = null,
    ) {
    }

    /**
     * Преобразует значение из модели (ID файла) в значение для view.
     * Если есть существующий файл - копируем его в draft area и возвращаем draft itemid.
     * Это нужно для редактирования - файл сначала попадает в draft, потом при submit переносится обратно.
     */
    public function transform($value): mixed
    {
        // Если нет значения - ничего не делаем
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        // Если это ID существующего файла - копируем в draft
        $fileId = is_string($value) ? (int)$value : $value;

        // Проверяем - это уже draft?
        $file = $this->entityManager->getRepository(File::class)->findOneBy([
            'component' => $this->component,
            'filearea'  => 'draft',
            'itemid'    => $fileId,
        ]);

        if ($file instanceof File) {
            // Уже draft - возвращаем как есть
            return (string)$fileId;
        }

        // Ищем файл в permanent area
        $permanentFile = $this->entityManager->getRepository(File::class)->find($fileId);

        if (!$permanentFile instanceof File) {
            // Файл не найден
            return null;
        }

        // Проверяем - может уже есть draft копия этого файла?
        // Ищем по referencefileid (ссылка на оригинальный файл)
        $existingDraft = $this->entityManager->getRepository(File::class)->findOneBy([
            'component'       => $this->component,
            'filearea'        => 'draft',
            'referencefileid' => $fileId,
        ]);

        if ($existingDraft instanceof File) {
            // Уже есть draft копия - используем её (не создаем новую!)
            return (string)$existingDraft->getItemid();
        }

        // Генерируем новый draft itemid
        $draftItemId = $this->generateDraftItemId();

        // Копируем файл в draft
        $draftFile = $this->fileService->copyToDraft($fileId, $draftItemId);

        if (!$draftFile instanceof File) {
            // Не удалось скопировать
            return null;
        }
        // Возвращаем draft itemid (который хранится в itemid draft файла)
        return (string)$draftFile->getItemid();
    }

    /**
     * Генерирует уникальный draft item ID.
     */
    private function generateDraftItemId(): int
    {
        return (int)(time() . rand(1000, 9999));
    }

    /**
     * Преобразует значение из view (строку ID файла или UploadedFile) обратно в модель.
     *
     * Поддерживает два режима:
     * 1. JS загрузчики (Dropzone, etc.) - файл загружается через AJAX, возвращается ID как строка
     * 2. Стандартная загрузка - файл приходит как UploadedFile
     */
    public function reverseTransform($value): ?string
    {
        // Если пустое значение
        if ($value === null || $value === '') {
            return null;
        }


        // Если пришла строка - это результат AJAX загрузки через JS виджет
        // Может быть draft itemid (число) или ID уже существующего файла
        if (is_string($value) || is_int($value)) {
            $numericValue = is_string($value) ? (int)$value : $value;

            // Проверяем - это draft файл?
            // Ищем файл в draft area по itemid
            $draftFile = $this->entityManager->getRepository(File::class)->findOneBy([
                'component' => $this->component,
                'filearea'  => 'draft',
                'itemid'    => $numericValue,
            ]);

            if ($draftFile instanceof File) {
                // Это draft - нужно переместить в permanent area
                return $this->moveFromDraftArea($numericValue);
            }

            // Это обычный ID файла - возвращаем как есть
            return (string)$numericValue;
        }

        // Если пришёл UploadedFile - обрабатываем стандартную загрузку
        // (этот случай для совместимости, если кто-то использует без JS)
        if ($value instanceof UploadedFile) {
            try {
                // Получаем значения параметров
                $itemid = $this->itemid;
                $contextid = $this->contextid;
                $userid = $this->userid;

                // Если установлен resolver, используем его для получения динамических значений из данных формы
                if ($this->formDataResolver !== null) {
                    $formData = ($this->formDataResolver)();

                    // Если itemid = 0 (значение по умолчанию), пытаемся получить из данных формы
                    if ($itemid === 0 && is_object($formData) && method_exists($formData, 'getId')) {
                        $itemid = $formData->getId() ?? 0;
                    }

                    // Если userid = null, пытаемся получить из данных формы
                    if ($userid === null && is_object($formData) && method_exists($formData, 'getId')) {
                        $userid = $formData->getId();
                    }
                }

                // Создаем File entity через сервис
                // FileService уже делает persist и flush, а также диспетчеризует события
                $file = $this->fileService->createFileFromUploaded(
                    $value,
                    component: $this->component,
                    filearea: $this->filearea,
                    itemid: $itemid,
                    contextid: $contextid,
                    userid: $userid
                );

                // Возвращаем ID файла как строку
                return $file->getId() !== null ? (string)$file->getId() : null;
            } catch (Throwable $e) {
                throw new TransformationFailedException(sprintf('Failed to transform uploaded file: %s', $e->getMessage()), 0, $e);
            }
        }

        return null;
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
            throw new TransformationFailedException(
                sprintf('Failed to move file from draft: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
