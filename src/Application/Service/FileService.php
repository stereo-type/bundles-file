<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Service;

use Slcorp\FileBundle\Application\Event\PostPersistEvent;
use Slcorp\FileBundle\Application\Event\PostUploadEvent;
use Slcorp\FileBundle\Application\Event\PreUploadEvent;
use Slcorp\FileBundle\Domain\Entity\File;
use Slcorp\FileBundle\Domain\Entity\FileRepositoryInterface;
use Slcorp\RoleModelBundle\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @copyright  2025 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class FileService
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private Filesystem $filesystem,
        private EventDispatcherInterface $eventDispatcher,
        private UserRepositoryInterface $userRepository,
        private FileRepositoryInterface $fileRepository,
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
        $user = $userid ? $this->userRepository->find($userid) : null;

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
        $contenthash = sha1($content ?: '');

        $pathname = $uploadedFile->getClientOriginalName();
        $pathnamehash = sha1($pathname);

        // Сохраняем файл по структуре хешей (как в Moodle)
        $storagePath = $this->parameterBag->get('slcorp_file.storage_path');

        // Создаем директорию, если её нет
        if (!$this->filesystem->exists($storagePath)) {
            $this->filesystem->mkdir($storagePath, 0755);
        }

        $filePath = $this->getFilePathFromHash($contenthash);
        $fullPath = $storagePath . \DIRECTORY_SEPARATOR . $filePath;

        // Создаем директории если нужно
        $dir = dirname($fullPath);
        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0755);
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
        // TODO придумать что то с папками  надо будет
        //        $fileDir = dirname($filePath);
        //        $file->setFilepath('/' . str_replace('\\', '/', $fileDir) . '/');
        $file->setFilepath('/');
        $file->setFilename($uploadedFile->getClientOriginalName());
        $file->setUserid($userid);
        $file->setFilesize($uploadedFile->getSize());
        $file->setMimetype($uploadedFile->getMimeType());
        $file->setStatus(0); // 0 = нормальный файл
        $file->setTimecreated(time());
        $file->setTimemodified(time());
        $file->setSortorder(0);
        $file->setAuthor($user?->getFullName());

        // Диспетчеризуем событие PostUploadEvent (после загрузки файла, но до сохранения в БД)
        $postUploadEvent = new PostUploadEvent($uploadedFile, $file, $fullPath);
        $this->eventDispatcher->dispatch($postUploadEvent, PostUploadEvent::NAME);

        $this->fileRepository->save($file);

        // Диспетчеризуем событие PostPersistEvent (после сохранения в БД)
        $postPersistEvent = new PostPersistEvent($file);
        $this->eventDispatcher->dispatch($postPersistEvent, PostPersistEvent::NAME);

        return $file;
    }

    /**
     * Перемещает файл из draft area в permanent area (как в Moodle).
     *
     * @param int $draftItemId Уникальный ID draft area (itemid в draft)
     * @param string $component Компонент назначения
     * @param string $filearea Область файла назначения
     * @param int $itemid ID элемента назначения
     * @param int $contextid ID контекста
     * @return File|null Перемещенный файл или null если не найден
     */
    public function moveFromDraft(
        int $draftItemId,
        string $component,
        string $filearea,
        int $itemid,
        int $contextid
    ): ?File {
        // Находим файл в draft area:
        // - component = тот же (для идентификации)
        // - filearea = 'draft'
        // - itemid = draftItemId
        $file = $this->fileRepository->findOneBy([
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftItemId,
        ]);

        if (!$file instanceof File) {
            return null;
        }

        // Перемещаем в permanent area
        $file->setFilearea($filearea);
        $file->setComponent($component);
        $file->setItemid($itemid); // draftItemId → user_id
        $file->setContextid($contextid);
        $file->setTimemodified(time());

        $this->fileRepository->save($file);

        return $file;
    }

    /**
     * Копирует файл из permanent area в draft area (для редактирования).
     * Используется когда открывается форма редактирования с уже загруженным файлом.
     *
     * @param int $fileId ID существующего файла
     * @param int $draftItemId Новый draft item ID
     * @return File|null Скопированный файл в draft или null если не найден
     */
    public function copyToDraft(int $fileId, int $draftItemId, bool $flush = true): ?File
    {
        // Находим оригинальный файл
        $originalFile = $this->fileRepository->find($fileId);

        if (!$originalFile instanceof File) {
            return null;
        }

        // Создаем копию в draft area
        $draftFile = new File();
        $draftFile->setContenthash($originalFile->getContenthash());
        $draftFile->setPathnamehash($originalFile->getPathnamehash());
        $draftFile->setContextid($originalFile->getContextid());
        $draftFile->setComponent('user');
        $draftFile->setFilearea('draft');
        $draftFile->setItemid($draftItemId);
        $draftFile->setFilepath($originalFile->getFilepath());
        $draftFile->setFilename($originalFile->getFilename());
        $draftFile->setUserid($originalFile->getUserid());
        $draftFile->setFilesize($originalFile->getFilesize());
        $draftFile->setMimetype($originalFile->getMimetype());
        $draftFile->setStatus($originalFile->getStatus());
        $draftFile->setTimecreated(time());
        $draftFile->setTimemodified(time());
        $draftFile->setSortorder($originalFile->getSortorder());
        $draftFile->setAuthor($originalFile->getAuthor());
        $draftFile->setReferencefileid($originalFile->getId());
        $this->fileRepository->save($draftFile, $flush);

        return $draftFile;
    }

    public function createEmptyDraftFile(int $draftItemId, ?int $userid, bool $flush = true): File
    {
        $draftFile = new File();
        $draftFile->setContenthash(''); // Пустой хеш
        $draftFile->setPathnamehash('');
        $draftFile->setContextid(1);
        $draftFile->setComponent('user');
        $draftFile->setFilearea('draft');
        $draftFile->setItemid($draftItemId);
        $draftFile->setFilepath('/');
        $draftFile->setFilename('');
        $draftFile->setUserid($userid);
        $draftFile->setFilesize(0);
        $draftFile->setMimetype('');
        $draftFile->setStatus(0);
        $draftFile->setTimecreated(time());
        $draftFile->setTimemodified(time());
        $draftFile->setSortorder(0);
        $draftFile->setAuthor(null);

        return $this->fileRepository->save($draftFile, $flush);
    }

    /**
     * Удаляет файл из базы данных и файловой системы.
     * Физический файл удаляется только если нет других записей с таким же contenthash.
     *
     * @param int $fileId ID файла для удаления
     * @param bool $flush Нужно ли сразу выполнить flush (по умолчанию false, чтобы не нарушать транзакции)
     * @return bool true если файл был удален, false если не найден
     */
    public function deleteFile(int $fileId, bool $flush = false): bool
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file instanceof File) {
            return false;
        }
        if (!$id = $file->getId()) {
            return false;
        }

        $contenthash = $file->getContenthash();

        // Проверяем, есть ли другие записи с таким же contenthash
        $otherFilesCount = $this->fileRepository->getCountSameFiles($contenthash, $id);

        // Удаляем физический файл только если нет других записей с таким же contenthash
        if ($otherFilesCount === 0) {
            $storagePath = $this->parameterBag->get('slcorp_file.storage_path');
            $filePath = $this->getFilePathFromHash($contenthash);
            $fullPath = $storagePath . \DIRECTORY_SEPARATOR . $filePath;

            if ($this->filesystem->exists($fullPath)) {
                $this->filesystem->remove($fullPath);
            }
        }

        $this->fileRepository->delete($file, $flush);

        return true;
    }

    /**
     * Удаляет старые файлы из draft area.
     *
     * @param int $olderThanDays Удалять файлы старше указанного количества дней
     * @return int Количество удаленных файлов
     */
    public function deleteOldDraftFiles(int $olderThanDays = 7): int
    {
        $timestamp = time() - ($olderThanDays * 24 * 60 * 60);

        $draftFiles = $this->fileRepository->getFilesOlderThen($timestamp);

        $deletedCount = 0;
        $contenthashesToCheck = [];

        foreach ($draftFiles as $file) {
            $contenthash = $file->getContenthash();

            if (!$id = $file->getId()) {
                continue;
            }
            // Проверяем, есть ли другие записи с таким же contenthash (включая другие draft файлы)
            // Используем ID текущего файла, чтобы не учитывать его при подсчете
            $otherFilesCount = $this->fileRepository->getCountSameFiles($contenthash, $id);

            // Удаляем физический файл только если нет других записей с таким же contenthash
            // и мы еще не проверяли этот contenthash
            if ($otherFilesCount === 0 && !in_array($contenthash, $contenthashesToCheck, true)) {
                $storagePath = $this->parameterBag->get('slcorp_file.storage_path');
                $filePath = $this->getFilePathFromHash($contenthash);
                $fullPath = $storagePath . \DIRECTORY_SEPARATOR . $filePath;

                if ($this->filesystem->exists($fullPath)) {
                    $this->filesystem->remove($fullPath);
                }

                $contenthashesToCheck[] = $contenthash;
            }

            // Удаляем запись из БД
            $this->fileRepository->delete($file, false);
            $deletedCount++;
        }

        $this->fileRepository->flush();

        return $deletedCount;
    }

    /**
     * Генерирует путь к файлу на основе хеша (как в Moodle)
     * Например: a1/b2/c3/d4/e5/f6/.../a1b2c3d4e5f6...
     */
    public function getFilePathFromHash(string $hash): string
    {
        // Берем первые 2 символа для первого уровня
        $level1 = mb_substr($hash, 0, 2);
        // Следующие 2 символа для второго уровня
        $level2 = mb_substr($hash, 2, 2);
        // Следующие 2 символа для третьего уровня
        $level3 = mb_substr($hash, 4, 2);

        return $level1 . '/' . $level2 . '/' . $level3 . '/' . $hash;
    }

    /**
     * Нормализует значение в массив ID файлов.
     * Поддерживает: массив, строку с разделителями (запятая, пробел), одиночное значение.
     */
    public function normalizeToArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter(array_map('intval', $value));
        }

        if (is_string($value)) {
            // Пробуем JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === \JSON_ERROR_NONE && is_array($decoded)) {
                return array_filter(array_map('intval', $decoded));
            }

            // Пробуем разделители (запятая, пробел, точка с запятой)
            $parts = preg_split('/[,;\s]+/', $value, -1, \PREG_SPLIT_NO_EMPTY);
            if (empty($parts)) {
                return [];
            }

            return array_filter(array_map('intval', $parts));
        }

        // Одиночное значение
        $intValue = is_numeric($value) ? (int) $value : null;

        return $intValue !== null ? [$intValue] : [];
    }
}
