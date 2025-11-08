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
     * Возвращаем ID файла как есть, чтобы он попал в $view->vars['value']
     * и использовался в buildView для отображения превью.
     */
    public function transform($value): mixed
    {
        // Возвращаем значение как есть (ID файла)
        // Оно будет использовано в buildView для получения информации о файле
        return $value;
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


        // Если пришла строка (ID файла) - это результат AJAX загрузки через JS виджет
        // Просто возвращаем её как есть
        if (is_string($value)) {
            return $value;
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
}
