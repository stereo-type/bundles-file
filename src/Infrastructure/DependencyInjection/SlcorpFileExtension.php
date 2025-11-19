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

        // Регистрируем путь к переводам бандла
        $bundlePath = \dirname(__DIR__, 3);
        $translationsPath = $bundlePath . '/translations';
        if (file_exists($translationsPath)) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [
                        $translationsPath,
                    ],
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('slcorp_file.adapter', $config['adapter']);
        $container->setParameter('slcorp_file.ui_library', $config['ui_library'] ?? 'fineuploader');
        $container->setParameter('slcorp_file.debug', $config['debug'] ?? false);
        // Разрешаем параметры в storage_path (например, %kernel.project_dir%)
        $storagePath = $container->getParameterBag()->resolveValue($config['storage_path']);
        $container->setParameter('slcorp_file.storage_path', $storagePath);

        // Обработка конфигурации валидации
        $validationConfig = $config['validation'] ?? [];
        $allowedMimeTypes = $validationConfig['mime_types'] ?? [];
        $maxSize = $this->parseMaxSize($validationConfig['max_size'] ?? '20M');
        $maxFiles = $validationConfig['max_files'] ?? 1;

        // Сохраняем настройки валидации как параметры для использования в формах
        $container->setParameter('slcorp_file.validation.mime_types', $allowedMimeTypes);
        $container->setParameter('slcorp_file.validation.max_size', $validationConfig['max_size'] ?? null);
        $container->setParameter('slcorp_file.validation.max_files', $maxFiles);

        // Регистрируем валидатор файлов
        $validatorDefinition = new Definition(FileValidator::class, [
            $allowedMimeTypes,
            $maxSize,
        ]);
        $validatorDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition(FileValidator::class, $validatorDefinition);

        $this->addDoctrineMappings($container);
        $this->addRoutes($container);
        $this->installAssets($container);
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
     * Устанавливает npm зависимости и копирует ассеты бандла.
     * Выполняется только в dev окружении и только из CLI (не из веб-запросов).
     */
    private function installAssets(ContainerBuilder $container): void
    {
        // Выполняем только в dev окружении
        $environment = $container->getParameter('kernel.environment');
        if ($environment !== 'dev') {
            return;
        }

        // Выполняем только из CLI (не из веб-запросов)
        if (\PHP_SAPI !== 'cli') {
            return;
        }

        $debug = $container->getParameter('slcorp_file.debug');
        $bundlePath = \dirname(__DIR__, 3);
        $packageJsonPath = $bundlePath . '/package.json';

        if ($debug) {
            echo "  [SlcorpFileBundle] Checking assets installation (dev + CLI only)...\n";
        }

        // Проверяем наличие package.json
        if (!file_exists($packageJsonPath)) {
            if ($debug) {
                echo "  [SlcorpFileBundle] package.json not found, skipping asset installation.\n";
            }

            return;
        }

        if ($debug) {
            echo "  [SlcorpFileBundle] Building assets...\n";
        }

        // Проверяем наличие npm
        $npmPath = $this->findNpm();
        if ($npmPath === null) {
            if ($debug) {
                echo "  [SlcorpFileBundle] WARNING: npm is not installed.\n";
                echo "  [SlcorpFileBundle] Please install npm and run: cd {$bundlePath} && npm run install-assets\n";
            }
            if ($debug) {
                trigger_error(
                    'npm is not installed. Please install npm and run: cd ' . $bundlePath . ' && npm run install-assets',
                    \E_USER_WARNING
                );
            }

            return;
        }

        if ($debug) {
            echo "  [SlcorpFileBundle] Found npm at: {$npmPath}\n";
            echo "  [SlcorpFileBundle] Installing npm dependencies...\n";
        }

        $originalDir = getcwd();
        if (!$originalDir) {
            return;
        }
        chdir($bundlePath);

        try {
            // Устанавливаем зависимости
            $output = [];
            $returnCode = 0;
            exec($npmPath . ' install 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                if ($debug) {
                    echo "  [SlcorpFileBundle] ERROR: npm install failed:\n";
                    foreach ($output as $line) {
                        echo "    {$line}\n";
                    }
                }
                if ($container->getParameter('kernel.debug')) {
                    trigger_error('npm install failed: ' . implode("\n", $output), \E_USER_WARNING);
                }

                return;
            }

            if ($debug) {
                echo "  [SlcorpFileBundle] npm dependencies installed successfully.\n";
                echo "  [SlcorpFileBundle] Building assets (copying vendor files, JS and compiling SCSS)...\n";
            }

            // Собираем ассеты (копируем vendor файлы, JS и компилируем SCSS)
            $output2 = [];
            $returnCode2 = 0;
            exec($npmPath . ' run build 2>&1', $output2, $returnCode2);

            if ($returnCode2 !== 0) {
                if ($debug) {
                    echo "  [SlcorpFileBundle] ERROR: npm build failed:\n";
                    foreach ($output2 as $line) {
                        echo "    {$line}\n";
                    }
                }
                if ($container->getParameter('kernel.debug')) {
                    trigger_error('npm build failed: ' . implode("\n", $output2), \E_USER_WARNING);
                }

                return;
            }

            if ($debug) {
                echo "  [SlcorpFileBundle] Assets built and copied successfully.\n";
                echo "  [SlcorpFileBundle] Asset installation completed!\n";
            }
        } finally {
            chdir($originalDir);
        }
    }

    /**
     * Находит путь к npm.
     */
    private function findNpm(): ?string
    {
        // Проверяем стандартные пути
        $commands = ['npm', 'npm.cmd'];
        foreach ($commands as $cmd) {
            $output = [];
            $returnCode = 0;
            exec('which ' . escapeshellarg($cmd) . ' 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return mb_trim($output[0]);
            }

            // Для Windows
            exec('where ' . escapeshellarg($cmd) . ' 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return mb_trim($output[0]);
            }
        }

        return null;
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
}
