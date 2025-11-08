<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Application\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Slcorp\FileBundle\Application\Enum\FileAdapter;
use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Slcorp\FileBundle\Application\Form\DataTransformer\UniversalFileTransformer;
use Slcorp\FileBundle\Application\Service\FileService;
use Slcorp\FileBundle\Domain\Entity\File;
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
 */
class UniversalFileType extends AbstractType
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Нормализуем ui_library для передачи в шаблон
        $uiLibrary = $options['ui_library'];
        if ($uiLibrary instanceof FileUILibrary) {
            $uiLibrary = $uiLibrary->value;
        } elseif ($uiLibrary === null) {
            $uiLibrary = $this->parameterBag->get('slcorp_file.ui_library') ?? 'fineuploader';
        }
        // Устанавливаем переменные для шаблона
        $builder->setAttribute('ui_library', $uiLibrary);
        $builder->setAttribute('component', $options['component'] ?? null);
        $builder->setAttribute('filearea', $options['filearea'] ?? null);
        $builder->setAttribute('itemid', $options['itemid'] ?? 0);
        $builder->setAttribute('contextid', $options['contextid'] ?? 1);
        $builder->setAttribute('userid', $options['userid'] ?? null);

        $adapterString = $options['adapter']?->value ?? $this->parameterBag->get('slcorp_file.adapter');
        $adapter = is_string($adapterString) ? FileAdapter::from($adapterString) : $adapterString;

        // Если поле mapped (привязано к модели), добавляем трансформер
        if ($options['mapped'] ?? true) {
            // Проверяем обязательные параметры
            if (empty($options['component']) || empty($options['filearea'])) {
                throw new InvalidArgumentException('Параметры "component" и "filearea" обязательны для UniversalFileType с mapped = true');
            }

            // Добавляем трансформер в зависимости от адаптера
            if ($adapter === FileAdapter::SONATA) {
                // TODO: Реализация для Sonata Media Bundle
            } elseif ($adapter === FileAdapter::VICH) {
                // TODO: Реализация для Vich Uploader Bundle
            }

            // Создаем замыкание для получения формы в момент трансформации
            $formRef = null;
            $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use (&$formRef) {
                $formRef = $event->getForm();
            });

            $transformer = new UniversalFileTransformer(
                $adapter,
                $this->fileService,
                $this->entityManager,
                $options['component'],
                $options['filearea'],
                $options['itemid'] ?? 0,
                $options['contextid'] ?? 1,
                $options['userid'] ?? null,
                static function () use (&$formRef) {
                    return $formRef?->getParent()?->getData();
                }
            );

            $builder->addModelTransformer($transformer);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
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
            $allowedMimeTypes = $this->parameterBag->get('slcorp_file.validation.mime_types') ?? [];
            if (!empty($allowedMimeTypes) && is_array($allowedMimeTypes)) {
                // Преобразуем MIME типы в формат для Dropzone/FineUploader
                $view->vars['attr']['data-allowed-extensions'] = implode(',', $allowedMimeTypes);
            }
        }

        if (!isset($view->vars['attr']['data-max-size'])) {
            $maxSize = $this->parameterBag->get('slcorp_file.validation.max_size') ?? null;
            if ($maxSize) {
                // Парсим размер в байты
                $view->vars['attr']['data-max-size'] = $this->parseMaxSizeToBytes($maxSize);
            }
        }

        // Если есть значение (ID файла), загружаем информацию о файле для превью
        $fileData = null;
        if (!empty($view->vars['value'])) {
            $fileId = is_numeric($view->vars['value']) ? (int)$view->vars['value'] : null;
            if ($fileId) {
                $file = $this->entityManager->getRepository(File::class)->find($fileId);
                if ($file instanceof File) {
                    $fileData = [
                        'id'           => $file->getId(),
                        'filename'     => $file->getFilename(),
                        'filesize'     => $file->getFilesize(),
                        'mimetype'     => $file->getMimetype(),
                        'download_url' => null, // Будет сгенерирован в Twig
                    ];
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
            return (int)$maxSize;
        }

        // Парсим строку с суффиксом
        $maxSize = trim($maxSize);
        $unit = strtoupper(substr($maxSize, -1));
        $value = (int)substr($maxSize, 0, -1);

        return match ($unit) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            default => (int)$maxSize,
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $uiLibraryDefault = $this->parameterBag->get('slcorp_file.ui_library') ?? 'fineuploader';
        $resolver->setDefaults([
            'required'   => false,
            'mapped'     => true, // По умолчанию привязано к модели
            'adapter'    => null, // Можно переопределить глобальный адаптер для конкретного поля (FileAdapter enum)
            'ui_library' => $uiLibraryDefault, // UI библиотека для загрузки файлов (FileUILibrary enum или строка)
            'component'  => null, // Компонент для сохранения файла (обязательно, если mapped = true)
            'filearea'   => null, // Область файла (обязательно, если mapped = true)
            'itemid'     => 0, // ID элемента
            'contextid'  => 1, // ID контекста
            'userid'     => null, // ID пользователя (опционально)
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
