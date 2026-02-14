<?php

declare(strict_types=1);

namespace P3s\YtDlp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('yt_dlp');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('binary_path')
                    ->defaultValue('yt-dlp')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('default_arguments')
                    ->scalarPrototype()->end()
                    ->defaultValue(['--no-warnings'])
                ->end()
                ->scalarNode('working_directory')
                    ->defaultNull()
                ->end()
                ->floatNode('timeout')
                    ->min(0)
                    ->defaultValue(300.0)
                ->end()
                ->arrayNode('environment')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
