<?php

declare(strict_types=1);

namespace Slcorp\FileBundle;

use Slcorp\FileBundle\Infrastructure\DependencyInjection\SlcorpFileExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class SlcorpFileBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SlcorpFileExtension();
    }
}
