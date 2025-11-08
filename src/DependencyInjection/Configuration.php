<?php

declare(strict_types=1);

namespace Slcorp\FileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @package    Configuration
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
                // Здесь будут параметры конфигурации бандла
            ->end();

        return $treeBuilder;
    }
}

