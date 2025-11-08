<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Enum;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum FileAdapter: string
{
    case SONATA = 'sonata';
    case VICH = 'vich';

    public function getLabel(): string
    {
        return match ($this) {
            self::SONATA => 'Sonata Media Bundle',
            self::VICH => 'Vich Uploader Bundle',
        };
    }
}
