<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ValueError;

/**
 * Роутер контроллер, который перенаправляет запросы на нужный UI-специфичный контроллер.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class UploadRouterController
{
    public function __construct(
        private ControllerFactory $controllerFactory,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * Роутит запрос на загрузку файла к нужному контроллеру в зависимости от UI библиотеки.
     */
    #[Route(
        path: '/file/upload/{ui_library}',
        name: 'slcorp_file_upload',
        requirements: ['ui_library' => 'fineuploader|dropzone|jquery_file_upload|plupload|uploadify|bluimp'],
        defaults: ['ui_library' => 'fineuploader'],
        methods: ['POST']
    )]
    public function upload(Request $request, string $ui_library = 'fineuploader'): Response
    {
        // Если UI библиотека не указана в URL, берем из параметров запроса или конфига
        if ($ui_library === 'fineuploader' && $request->request->has('ui_library')) {
            $ui_library = $request->request->get('ui_library');
        }

        // Если все еще не указана, берем из глобального конфига
        if ($ui_library === 'fineuploader') {
            /**@phpstan-ignore-next-line */
            $ui_library = $this->parameterBag->get('slcorp_file.ui_library') ?? FileUILibrary::FINE_UPLOADER->value;
        }

        try {
            $library = FileUILibrary::from((string)$ui_library);
        } catch (ValueError) {
            // Если библиотека не найдена, используем значение по умолчанию
            $library = FileUILibrary::FINE_UPLOADER;
        }

        $controller = $this->controllerFactory->getController($library);

        return $controller->upload($request);
    }
}
