<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\EventListener;

use Slcorp\FileBundle\Application\Event\PostPersistEvent;
use Slcorp\FileBundle\Application\Event\PostUploadEvent;
use Slcorp\FileBundle\Application\Event\PreUploadEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Пример EventListener для обработки событий загрузки файлов.
 * Этот класс демонстрирует, как можно использовать события FileBundle.
 *
 * Для использования создайте свой EventListener и зарегистрируйте его в services.yaml:
 *
 * ```yaml
 * services:
 *     App\EventListener\CustomFileUploadListener:
 *         tags:
 *             - { name: kernel.event_listener, event: slcorp_file.pre_upload, method: onPreUpload }
 *             - { name: kernel.event_listener, event: slcorp_file.post_upload, method: onPostUpload }
 *             - { name: kernel.event_listener, event: slcorp_file.post_persist, method: onPostPersist }
 * ```
 *
 * Или используйте атрибут AsEventListener:
 *
 * ```php
 * #[AsEventListener(event: PreUploadEvent::NAME)]
 * public function onPreUpload(PreUploadEvent $event): void
 * {
 *     // Ваша логика
 * }
 * ```
 *
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ExampleFileUploadListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PreUploadEvent::NAME   => 'onPreUpload',
            PostUploadEvent::NAME  => 'onPostUpload',
            PostPersistEvent::NAME => 'onPostPersist',
        ];
    }

    /**
     * Вызывается перед загрузкой файла (до валидации).
     * Здесь можно добавить дополнительную логику проверки.
     */
    public function onPreUpload(PreUploadEvent $event): void
    {
        $file = $event->getFile();
        // Пример: логирование информации о файле
        // error_log(sprintf('Загрузка файла: %s, размер: %d байт', $file->getClientOriginalName(), $file->getSize()));
    }

    /**
     * Вызывается после загрузки файла на диск, но до сохранения в БД.
     * Здесь можно обработать файл (например, создать миниатюры для изображений).
     */
    public function onPostUpload(PostUploadEvent $event): void
    {
        $file = $event->getFile();
        $fileEntity = $event->getFileEntity();
        $fullPath = $event->getFullPath();

        // Пример: создание миниатюр для изображений
        // if (str_starts_with($file->getMimeType() ?? '', 'image/')) {
        //     $this->createThumbnail($fullPath);
        // }
    }

    /**
     * Вызывается после сохранения File entity в БД.
     * Здесь можно выполнить действия, требующие наличия ID файла в БД.
     */
    public function onPostPersist(PostPersistEvent $event): void
    {
        $file = $event->getFile();

        // Пример: отправка уведомления или интеграция с внешними системами
        // $this->notifyExternalSystem($file);
    }
}
