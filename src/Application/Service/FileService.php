<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Service;

use Doctrine\ORM\EntityManagerInterface;
use Slcorp\FileBundle\Application\Event\PostPersistEvent;
use Slcorp\FileBundle\Application\Event\PostUploadEvent;
use Slcorp\FileBundle\Application\Event\PreUploadEvent;
use Slcorp\FileBundle\Domain\Entity\File;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class FileService
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private Filesystem $filesystem,
        private EventDispatcherInterface $eventDispatcher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createFileFromUploaded(
        UploadedFile $uploadedFile,
        string $component,
        string $filearea,
        int $itemid = 0,
        int $contextid = 1,
        ?int $userid = null,
    ): File {
        // Диспетчеризуем событие PreUploadEvent для валидации
        $preUploadEvent = new PreUploadEvent(
            $uploadedFile,
            $component,
            $filearea,
            $itemid,
            $contextid,
            $userid
        );
        $this->eventDispatcher->dispatch($preUploadEvent, PreUploadEvent::NAME);

        // Вычисляем хеши
        $content = file_get_contents($uploadedFile->getPathname());
        $contenthash = sha1($content);

        $pathname = $uploadedFile->getClientOriginalName();
        $pathnamehash = sha1($pathname);

        // Сохраняем файл по структуре хешей (как в Moodle)
        $storagePath = $this->parameterBag->get('slcorp_file.storage_path');

        // Создаем директорию, если её нет
        if (!$this->filesystem->exists($storagePath)) {
            $this->filesystem->mkdir($storagePath, 0755, true);
        }

        $filePath = $this->getFilePathFromHash($contenthash);
        $fullPath = $storagePath . \DIRECTORY_SEPARATOR . $filePath;

        // Создаем директории если нужно
        $dir = dirname($fullPath);
        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0755, true);
        }

        // Сохраняем файл (в Moodle файл сохраняется с именем равным хешу содержимого)
        $this->filesystem->copy($uploadedFile->getPathname(), $fullPath, true);

        // Создаем File entity
        $file = new File();
        $file->setContenthash($contenthash);
        $file->setPathnamehash($pathnamehash);
        $file->setContextid($contextid);
        $file->setComponent($component);
        $file->setFilearea($filearea);
        $file->setItemid($itemid);
        // Сохраняем путь к директории относительно storage_path (для совместимости с Moodle)
        // filepath должен быть типа /a1/b2/c3/, где a1/b2/c3 - поддиректории из хеша
        $fileDir = dirname($filePath);
        $file->setFilepath('/' . str_replace('\\', '/', $fileDir) . '/');
        $file->setFilename($uploadedFile->getClientOriginalName());
        $file->setUserid($userid);
        $file->setFilesize($uploadedFile->getSize());
        $file->setMimetype($uploadedFile->getMimeType());
        $file->setStatus(0); // 0 = нормальный файл
        $file->setTimecreated(time());
        $file->setTimemodified(time());
        $file->setSortorder(0);

        // Диспетчеризуем событие PostUploadEvent (после загрузки файла, но до сохранения в БД)
        $postUploadEvent = new PostUploadEvent($uploadedFile, $file, $fullPath);
        $this->eventDispatcher->dispatch($postUploadEvent, PostUploadEvent::NAME);

        // Сохраняем File entity в БД
        $this->entityManager->persist($file);
        $this->entityManager->flush();

        // Диспетчеризуем событие PostPersistEvent (после сохранения в БД)
        $postPersistEvent = new PostPersistEvent($file);
        $this->eventDispatcher->dispatch($postPersistEvent, PostPersistEvent::NAME);

        return $file;
    }

    /**
     * Генерирует путь к файлу на основе хеша (как в Moodle)
     * Например: a1/b2/c3/d4/e5/f6/.../a1b2c3d4e5f6...
     */
    private function getFilePathFromHash(string $hash): string
    {
        // Берем первые 2 символа для первого уровня
        $level1 = mb_substr($hash, 0, 2);
        // Следующие 2 символа для второго уровня
        $level2 = mb_substr($hash, 2, 2);
        // Следующие 2 символа для третьего уровня
        $level3 = mb_substr($hash, 4, 2);

        return $level1 . '/' . $level2 . '/' . $level3 . '/' . $hash;
    }
}
