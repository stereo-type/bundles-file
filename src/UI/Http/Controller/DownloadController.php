<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\UI\Http\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Slcorp\FileBundle\Application\Service\FileService;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Контроллер для скачивания файлов.
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class DownloadController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileService $fileService,
    ) {
    }

    #[Route(
        path: '/file/download/{id}',
        name: 'slcorp_file_download',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function download(int $id): Response
    {
        // Получаем File entity из БД
        $file = $this->entityManager->getRepository(File::class)->find($id);

        if (!$file instanceof File) {
            throw $this->createNotFoundException('File not found');
        }

        // TODO: Добавить проверку прав доступа

        // Получаем путь к файлу
        $storagePath = $this->parameterBag->get('slcorp_file.storage_path');
        $debug = $this->parameterBag->get('app.debug');

        // Полный путь: storage_path + filepath + contenthash
        $fullPath = mb_rtrim($storagePath, '/') . '/' . $this->fileService->getFilePathFromHash($file->getContenthash());

        if (!file_exists($fullPath)) {
            $error = 'Physical file not found.';
            if ($debug) {
                $error .= ' Path: ' . $fullPath;
            }
            throw $this->createNotFoundException($error);
        }

        // Возвращаем файл
        $response = new BinaryFileResponse($fullPath);

        // Устанавливаем правильный MIME тип
        if ($file->getMimetype()) {
            $response->headers->set('Content-Type', $file->getMimetype());
        }

        // Для изображений показываем inline, для остальных - attachment (скачивание)
        $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        if ($file->getMimetype() && str_starts_with($file->getMimetype(), 'image/')) {
            $disposition = ResponseHeaderBag::DISPOSITION_INLINE;
        }

        $response->setContentDisposition(
            $disposition,
            $file->getFilename()
        );

        return $response;
    }
}
