<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Event;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Событие, которое вызывается перед загрузкой файла.
 * Используется для валидации файла перед его сохранением.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PreUploadEvent extends Event
{
    public const NAME = 'slcorp_file.pre_upload';

    public function __construct(
        private readonly UploadedFile $file,
        private readonly string $component,
        private readonly string $filearea,
        private readonly int $itemid,
        private readonly int $contextid,
        private readonly ?int $userid = null,
    ) {
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getFilearea(): string
    {
        return $this->filearea;
    }

    public function getItemid(): int
    {
        return $this->itemid;
    }

    public function getContextid(): int
    {
        return $this->contextid;
    }

    public function getUserid(): ?int
    {
        return $this->userid;
    }
}
