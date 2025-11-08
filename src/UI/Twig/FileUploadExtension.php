<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig расширение для работы с загрузкой файлов.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FileUploadExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('slcorp_file_upload_url', [$this, 'getUploadUrl']),
            new TwigFunction('slcorp_file_download_url', [$this, 'getDownloadUrl']),
            new TwigFunction('slcorp_file_delete_url', [$this, 'getDeleteUrl']),
        ];
    }

    /**
     * Получает URL для загрузки файла с указанной UI библиотекой.
     */
    public function getUploadUrl(?string $uiLibrary = null): string
    {
        $library = $uiLibrary ?? $this->parameterBag->get('slcorp_file.ui_library');

        return $this->urlGenerator->generate('slcorp_file_upload', [
            'ui_library' => $library,
        ]);
    }

    /**
     * Получает URL для скачивания файла.
     */
    public function getDownloadUrl(int $fileId): string
    {
        return $this->urlGenerator->generate('slcorp_file_download', [
            'id' => $fileId,
        ]);
    }

    /**
     * Получает URL для удаления файла.
     */
    public function getDeleteUrl(int $fileId): string
    {
        return $this->urlGenerator->generate('slcorp_file_delete', [
            'id' => $fileId,
        ]);
    }
}
