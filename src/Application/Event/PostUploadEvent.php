<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Event;

use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Событие, которое вызывается после загрузки файла, но до сохранения в БД.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PostUploadEvent extends Event
{
    public const NAME = 'slcorp_file.post_upload';

    public function __construct(
        private readonly UploadedFile $file,
        private readonly File $fileEntity,
        private readonly string $fullPath,
    ) {
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function getFileEntity(): File
    {
        return $this->fileEntity;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }
}
