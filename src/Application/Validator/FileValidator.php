<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Validator;

use Slcorp\FileBundle\Application\Event\PreUploadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Валидатор файлов по MIME-типам и размеру.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class FileValidator implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PreUploadEvent::NAME => 'onPreUpload',
        ];
    }

    /**
     * @param array<string> $allowedMimeTypes Разрешенные MIME-типы (пустой массив = разрешены все)
     * @param int|null $maxSize Максимальный размер файла в байтах (null = без ограничений)
     */
    public function __construct(
        private array $allowedMimeTypes = [],
        private ?int $maxSize = null,
    ) {
    }

    public function onPreUpload(PreUploadEvent $event): void
    {
        $file = $event->getFile();

        // Валидация размера файла
        if ($this->maxSize !== null && $file->getSize() > $this->maxSize) {
            throw new FileException(
                sprintf(
                    'Размер файла "%s" (%s) превышает максимально допустимый размер (%s)',
                    $file->getClientOriginalName(),
                    $this->formatBytes($file->getSize()),
                    $this->formatBytes($this->maxSize)
                )
            );
        }

        // Валидация MIME-типа
        if (!empty($this->allowedMimeTypes)) {
            $mimeType = $file->getMimeType();
            if ($mimeType === null || !in_array($mimeType, $this->allowedMimeTypes, true)) {
                throw new FileException(
                    sprintf(
                        'MIME-тип файла "%s" (%s) не разрешен. Разрешенные типы: %s',
                        $file->getClientOriginalName(),
                        $mimeType ?? 'неизвестен',
                        implode(', ', $this->allowedMimeTypes)
                    )
                );
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, 2) . ' ' . $units[(int) $pow];
    }
}
