<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Фабрика для получения контроллера в зависимости от UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ControllerFactory
{
    /** @var array<string, BaseUploadController> */
    private array $controllers = [];

    public function __construct(
        ServiceProviderInterface $controllers,
    ) {
        // Регистрируем контроллеры
        $this->controllers[FileUILibrary::FINE_UPLOADER->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\FineUploaderController');
        $this->controllers[FileUILibrary::DROPZONE->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\DropzoneController');
        $this->controllers[FileUILibrary::JQUERY_FILE_UPLOAD->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\JQueryFileUploadController');
        $this->controllers[FileUILibrary::PLUPLOAD->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\PluploadController');
        $this->controllers[FileUILibrary::UPLOADIFY->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\UploadifyController');
        $this->controllers[FileUILibrary::BLUIMP->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\BluimpController');
    }

    /**
     * Получает контроллер для указанной UI библиотеки.
     */
    public function getController(FileUILibrary|string $uiLibrary): BaseUploadController
    {
        $library = $uiLibrary instanceof FileUILibrary ? $uiLibrary->value : $uiLibrary;

        return $this->controllers[$library] ?? $this->controllers[FileUILibrary::FINE_UPLOADER->value];
    }
}
