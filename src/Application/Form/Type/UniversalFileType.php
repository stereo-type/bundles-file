<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Form\Type;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Slcorp\FileBundle\Application\Enum\FileAdapter;
use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Slcorp\FileBundle\Application\Form\DataTransformer\UniversalFileTransformer;
use Slcorp\FileBundle\Application\Service\FileService;
use Slcorp\FileBundle\Domain\Entity\File;
use Slcorp\FileBundle\Domain\Entity\FileRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @extends AbstractType<mixed>
 */
class UniversalFileType extends AbstractType
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileService $fileService,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
        private readonly FileRepositoryInterface $fileRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $logger = $this->logger;
        $logger->debug('Building form UniversalFileType');
        // Нормализуем ui_library для передачи в шаблон
        $uiLibrary = $options['ui_library'];
        if ($uiLibrary instanceof FileUILibrary) {
            $uiLibrary = $uiLibrary->value;
        } elseif ($uiLibrary === null) {
            $uiLibrary = $this->parameterBag->get('slcorp_file.ui_library') ?: 'fineuploader';
        }
        $user = $this->security->getUser();
        $userId = $options['userid'] ?? ($user && method_exists($user, 'getId') ?
            $user->getId() : null);
        // Устанавливаем переменные для шаблона
        $builder->setAttribute('ui_library', $uiLibrary);
        $builder->setAttribute('component', $options['component'] ?? null);
        $builder->setAttribute('filearea', $options['filearea'] ?? null);
        $builder->setAttribute('itemid', $options['itemid'] ?? 0);
        $builder->setAttribute('contextid', $options['contextid'] ?? 1);
        $builder->setAttribute('userid', $userId);

        /* @phpstan-ignore-next-line */
        $adapterString = $options['adapter']?->value ?? $this->parameterBag->get('slcorp_file.adapter');
        $adapter = is_string($adapterString) ? FileAdapter::from($adapterString) : $adapterString;

        // Проверяем обязательные параметры для трансформера
        if (empty($options['component']) || empty($options['filearea'])) {
            if ($options['mapped'] ?? true) {
                throw new InvalidArgumentException('Параметры "component" и "filearea" обязательны для UniversalFileType');
            }
        // Если mapped = false и параметры не указаны - просто не добавляем трансформер
        } else {
            // Получаем maxFiles из атрибутов или конфига
            /* @phpstan-ignore-next-line */
            $maxFiles = $options['attr']['data-max-files'] ?? $this->parameterBag->get('slcorp_file.validation.max_files') ?? 1;
            $maxFiles = is_numeric($maxFiles) ? (int) $maxFiles : 1;

            // Переменная для хранения формы и оригинального значения
            $formRef = null;
            $originalValueRef = null;
            // Счетчик вызовов transform
            $transformCallCount = 0;

            // Сохраняем оригинальное значение для трансформера через PRE_SET_DATA
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use (&$formRef, &$originalValueRef, &$transformCallCount, $logger) {
                $logger->debug('PRE_SET_DATA form UniversalFileType');
                $value = $event->getData();
                $form = $event->getForm();
                $formRef = $form;
                // Сохраняем оригинальное значение для трансформера
                $originalValueRef = $value;
                $transformCallCount++;
            });

            // Добавляем трансформер в зависимости от адаптера
            if ($adapter === FileAdapter::SONATA) {
                // TODO: Реализация для Sonata Media Bundle
            } elseif ($adapter === FileAdapter::VICH) {
                // TODO: Реализация для Vich Uploader Bundle
            }

            // Создаем замыкание для получения формы в момент трансформации
            $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use (&$formRef, $logger) {
                $logger->debug('PRE_SUBMIT form UniversalFileType');
                $formRef = $event->getForm();
            });

            // Создаем замыкание для получения оригинального значения
            $originalValueResolver = static function () use (&$originalValueRef) {
                return $originalValueRef;
            };

            // Создаем замыкание для получения и увеличения счетчика вызовов
            $transformCallCounter = static function () use (&$transformCallCount) {
                return $transformCallCount;
            };

            $transformer = new UniversalFileTransformer(
                $this->fileService,
                $this->fileRepository,
                $this->logger,
                (string)$options['component'],
                (string)$options['filearea'],
                $options['itemid'] ?? 0,
                $options['contextid'] ?? 1,
                $userId,
                static function () use (&$formRef) {
                    return $formRef?->getParent()?->getData();
                },
                $maxFiles,
                $originalValueResolver,
                $transformCallCounter
            );

            $builder->addModelTransformer($transformer);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->logger->debug('building view UniversalFileType');
        // Передаем атрибуты из builder в view
        $config = $form->getConfig();
        $view->vars['attr']['ui_library'] = $config->getAttribute('ui_library');
        $view->vars['attr']['component'] = $config->getAttribute('component');
        $view->vars['attr']['filearea'] = $config->getAttribute('filearea');
        $view->vars['attr']['itemid'] = $config->getAttribute('itemid');
        $view->vars['attr']['contextid'] = $config->getAttribute('contextid');
        $view->vars['attr']['userid'] = $config->getAttribute('userid');

        // Получаем настройки валидации из конфига (если не переопределены в атрибутах)
        if (!isset($view->vars['attr']['data-allowed-extensions'])) {
            $allowedMimeTypes = $this->parameterBag->get('slcorp_file.validation.mime_types') ?: [];
            if (!empty($allowedMimeTypes)) {
                // Преобразуем MIME типы в формат для Dropzone/FineUploader
                $view->vars['attr']['data-allowed-extensions'] = implode(',', $allowedMimeTypes);
            }
        }

        if (!isset($view->vars['attr']['data-max-size'])) {
            $maxSize = $this->parameterBag->get('slcorp_file.validation.max_size');
            if ($maxSize) {
                // Парсим размер в байты
                $view->vars['attr']['data-max-size'] = $this->parseMaxSizeToBytes($maxSize);
            }
        }

        if (!isset($view->vars['attr']['data-max-files'])) {
            $maxFiles = $this->parameterBag->get('slcorp_file.validation.max_files') ?: 1;
            $view->vars['attr']['data-max-files'] = (int) $maxFiles;
        }

        // Если есть значение (draft itemid или массив), загружаем информацию о файлах для превью
        $fileData = [];
        if (!empty($view->vars['value'])) {
            $component = $config->getAttribute('component');

            // Нормализуем значение в массив draft itemid (работает и со строкой, и с массивом)
            $draftItemIds = $this->fileService->normalizeToArray($view->vars['value']);

            foreach ($draftItemIds as $draftItemId) {
                // Ищем файл в draft area по itemid (это и есть draft itemid)
                $file = $this->fileRepository->findOneBy(
                    [
                        'component' => $component,
                        'filearea' => 'draft',
                        'itemid' => $draftItemId,
                    ]
                );

                if ($file instanceof File) {
                    if (!$file->isDraft()) {
                        $fileData[] = [
                            'id' => $file->getId(),
                            'filename' => $file->getFilename(),
                            'filesize' => $file->getFilesize(),
                            'mimetype' => $file->getMimetype(),
                            'download_url' => null, // Будет сгенерирован в Twig
                            'draftitemid' => $file->getItemid(), // Для JS виджетов
                        ];
                    }
                }
            }
        }
        $view->vars['file_data'] = $fileData;
    }

    /**
     * Парсит размер файла из строки (например, "2M", "500K", "1G") в байты.
     */
    private function parseMaxSizeToBytes(string|int $maxSize): int
    {
        if (is_int($maxSize)) {
            return $maxSize;
        }

        if (is_numeric($maxSize)) {
            return (int) $maxSize;
        }

        // Парсим строку с суффиксом
        $maxSize = mb_trim($maxSize);
        $unit = mb_strtoupper(mb_substr($maxSize, -1));
        $value = (int) mb_substr($maxSize, 0, -1);

        return match ($unit) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            default => (int) $maxSize,
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $uiLibraryDefault = $this->parameterBag->get('slcorp_file.ui_library') ?: 'fineuploader';
        $resolver->setDefaults([
            'required' => false,
            'mapped' => true, // По умолчанию привязано к модели
            'adapter' => null, // Можно переопределить глобальный адаптер для конкретного поля (FileAdapter enum)
            'ui_library' => $uiLibraryDefault, // UI библиотека для загрузки файлов (FileUILibrary enum или строка)
            'component' => null, // Компонент для сохранения файла (обязательно, если mapped = true)
            'filearea' => null, // Область файла (обязательно, если mapped = true)
            'itemid' => 0, // ID элемента
            'contextid' => 1, // ID контекста
            'userid' => null, // ID пользователя (опционально)
        ]);

        $resolver->setAllowedTypes('adapter', ['null', FileAdapter::class, 'string']);
        $resolver->setAllowedTypes('ui_library', ['null', FileUILibrary::class, 'string']);
        $resolver->setAllowedTypes('component', ['null', 'string']);
        $resolver->setAllowedTypes('filearea', ['null', 'string']);
        $resolver->setAllowedTypes('itemid', ['int', 'null']);
        $resolver->setAllowedTypes('contextid', ['int', 'null']);
        $resolver->setAllowedTypes('userid', ['null', 'int']);
    }

    public function getParent(): string
    {
        // Наследуемся от TextType, так как по факту это скрытое поле с ID файла
        // Стандартный FileType не нужен, так как используются JS загрузчики
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'slcorp_file';
    }
}
