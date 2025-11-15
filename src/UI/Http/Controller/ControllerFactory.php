<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Slcorp\FileBundle\Application\Enum\FileUILibrary;

/**
 * Фабрика для получения контроллера в зависимости от UI библиотеки.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ControllerFactory
{
    /** @var array<string, UploaderControllerInterface> */
    private array $controllers;

    public function __construct(
        iterable $controllers,
    ) {
        /** @var UploaderControllerInterface $controller */
        foreach ($controllers as $controller) {
            $this->controllers[$controller->library()->value] = $controller;
        }
        // Регистрируем контроллеры
        //        $this->controllers[FileUILibrary::DROPZONE->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\DropzoneController');
        //        $this->controllers[FileUILibrary::FINE_UPLOADER->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\FineUploaderController');
        //        $this->controllers[FileUILibrary::JQUERY_FILE_UPLOAD->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\JQueryFileUploadController');
        //        $this->controllers[FileUILibrary::PLUPLOAD->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\PluploadController');
        //        $this->controllers[FileUILibrary::BLUIMP->value] = $controllers->get('Slcorp\FileBundle\UI\Http\Controller\BluimpController');
    }

    /**
     * Получает контроллер для указанной UI библиотеки.
     */
    public function getController(FileUILibrary|string $uiLibrary): UploaderControllerInterface
    {
        $library = $uiLibrary instanceof FileUILibrary ? $uiLibrary : FileUILibrary::from($uiLibrary);

        return $this->controllers[$library->value];
    }
}
