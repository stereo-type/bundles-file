<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\Infrastructure\DependencyInjection;

use Slcorp\FileBundle\Application\Enum\FileAdapter;
use Slcorp\FileBundle\Application\Enum\FileUILibrary;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @copyright  2024 Zhalayletdinov Vyacheslav evil_tut@mail.ru
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('slcorp_file');

        $treeBuilder->getRootNode()
            ->children()
            ->enumNode('adapter')
            ->values(array_map(static fn (FileAdapter $adapter) => $adapter->value, FileAdapter::cases()))
            ->defaultValue(FileAdapter::VICH->value)
            ->info('Выбор адаптера для работы с файлами: sonata (SonataMediaBundle) или vich (VichUploaderBundle)')
            ->end()
            ->enumNode('ui_library')
            ->values(array_map(static fn (FileUILibrary $library) => $library->value, FileUILibrary::cases()))
            ->defaultValue(FileUILibrary::FINE_UPLOADER->value)
            ->info('UI библиотека для загрузки файлов по умолчанию')
            ->end()
            ->scalarNode('storage_path')
            ->isRequired()
            ->cannotBeEmpty()
            ->info('Путь для хранения файлов (обязательный параметр)')
            ->end()
            ->arrayNode('validation')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('mime_types')
            ->info('Разрешенные MIME-типы файлов (пустой массив = разрешены все типы)')
            ->example(['image/jpeg', 'image/png', 'application/pdf'])
            ->prototype('scalar')->end()
            ->defaultValue([])
            ->end()
            ->scalarNode('max_size')
            ->info('Максимальный размер файла в байтах (null = без ограничений). Можно использовать суффиксы: K, M, G (например: 2M)')
            ->defaultNull()
            ->end()
            ->integerNode('max_files')
            ->info('Максимальное количество файлов, которое можно загрузить')
            ->defaultValue(1)
            ->min(1)
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
