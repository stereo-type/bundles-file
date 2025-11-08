<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Infrastructure\DependencyInjection;

use Slcorp\FileBundle\Application\Validator\FileValidator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class SlcorpFileExtension extends Extension implements PrependExtensionInterface
{
    private const PERMISSIONS_MASK = 0755;

    public function prepend(ContainerBuilder $container): void
    {
        // Регистрируем путь к Twig шаблонам бандла
        $viewsPath = __DIR__ . '/../../Resources/views';

        $container->prependExtensionConfig('twig', [
            'paths' => [
                $viewsPath => 'SlcorpFileBundle',
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('slcorp_file.adapter', $config['adapter']);
        $container->setParameter('slcorp_file.ui_library', $config['ui_library'] ?? 'fineuploader');
        // Разрешаем параметры в storage_path (например, %kernel.project_dir%)
        $storagePath = $container->getParameterBag()->resolveValue($config['storage_path']);
        $container->setParameter('slcorp_file.storage_path', $storagePath);

        // Обработка конфигурации валидации
        $validationConfig = $config['validation'] ?? [];
        $allowedMimeTypes = $validationConfig['mime_types'] ?? [];
        $maxSize = $this->parseMaxSize($validationConfig['max_size'] ?? null);

        // Сохраняем настройки валидации как параметры для использования в формах
        $container->setParameter('slcorp_file.validation.mime_types', $allowedMimeTypes);
        $container->setParameter('slcorp_file.validation.max_size', $validationConfig['max_size'] ?? null);

        // Регистрируем валидатор файлов
        $validatorDefinition = new Definition(FileValidator::class, [
            $allowedMimeTypes,
            $maxSize,
        ]);
        $validatorDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition(FileValidator::class, $validatorDefinition);

        $this->addDoctrineMappings($container);
        $this->addRoutes($container);
    }

    private function addRoutes(ContainerBuilder $container): void
    {
        $filesystem = new Filesystem();
        $projectRoot = $container->getParameter('kernel.project_dir');
        $subDir = '/config/packages/routes';
        $filename = 'file_bundle.php';

        $projectRoutesDir = $projectRoot . $subDir;
        $bundleRoutesFile = __DIR__ . '/../../../config/packages/routes/file_bundle.php';
        $targetRoutesFile = $projectRoutesDir . '/' . $filename;

        if (!$filesystem->exists($projectRoutesDir)) {
            $filesystem->mkdir($projectRoutesDir, self::PERMISSIONS_MASK);
        }

        if (!$filesystem->exists($targetRoutesFile) && $filesystem->exists($bundleRoutesFile)) {
            $filesystem->copy($bundleRoutesFile, $targetRoutesFile);
        }
    }

    private function addDoctrineMappings(ContainerBuilder $container): void
    {
        $filesystem = new Filesystem();
        $projectRoot = $container->getParameter('kernel.project_dir');
        $subDir = '/config/packages/doctrine';
        $filename = 'file_bundle.php';

        $projectConfigDir = $projectRoot . $subDir;
        $bundleMappingFile = __DIR__ . '/../../Resources/config/packages/doctrine/file_bundle.php';
        $targetMappingFile = $projectConfigDir . '/' . $filename;

        if (!$filesystem->exists($projectConfigDir)) {
            $filesystem->mkdir($projectConfigDir, self::PERMISSIONS_MASK);
        }

        if (!$filesystem->exists($targetMappingFile)) {
            $filesystem->copy($bundleMappingFile, $targetMappingFile);
        }
    }

    public function getAlias(): string
    {
        return 'slcorp_file';
    }

    /**
     * Парсит размер файла из строки (например, "2M", "500K", "1G") в байты.
     */
    private function parseMaxSize(?string $maxSize): ?int
    {
        if ($maxSize === null || $maxSize === '') {
            return null;
        }

        // Если уже число, возвращаем как есть
        if (is_numeric($maxSize)) {
            return (int)$maxSize;
        }

        // Парсим строку с суффиксом
        $maxSize = mb_trim($maxSize);
        $unit = mb_strtoupper(mb_substr($maxSize, -1));
        $value = (int)mb_substr($maxSize, 0, -1);

        return match ($unit) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            default => (int)$maxSize,
        };
    }
}
