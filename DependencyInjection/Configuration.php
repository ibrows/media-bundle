<?php

namespace Ibrows\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ibrows_media');

        $this->addMediaSection($rootNode);
        $this->addUploadedImageSection($rootNode);
        $this->addUploadedFileSection($rootNode);

        return $treeBuilder;
    }

    protected function addMediaSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('upload_location')->isRequired()->end()
                ->scalarNode('upload_root')->isRequired()->end()
                ->scalarNode('template')->defaultValue('IbrowsMediaBundle:Media:blocks.html.twig')->end()
                ->arrayNode('enabled_types')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')
                ->end()
            ->end()
        ;
    }

    protected function addUploadedImageSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('image')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('max_width')->defaultNull()->end()
                        ->scalarNode('max_height')->defaultNull()->end()
                        ->scalarNode('max_size')->defaultNull()->end()
                        ->arrayNode('formats')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('width')->end()
                                    ->scalarNode('height')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('mime_types')
                            ->isRequired()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addUploadedFileSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('file')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('max_size')->defaultNull()->end()
                        ->arrayNode('mime_types')
                            ->isRequired()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
