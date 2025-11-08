<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Event;

use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Событие, которое вызывается после сохранения File entity в БД.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PostPersistEvent extends Event
{
    public const NAME = 'slcorp_file.post_persist';

    public function __construct(
        private readonly File $file,
    ) {
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
