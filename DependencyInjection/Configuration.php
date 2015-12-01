<?php

namespace Happyr\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('happyr_translation');

        $root->children()
            ->enumNode('translation_service')->values(array('loco', 'foobar'))->defaultValue('loco')->end()
            ->scalarNode('target_dir')->defaultValue('%kernel.root_dir%/Resources/translations')->end()
            ->booleanNode('auto_add_assets')->defaultFalse()->end()
            ->booleanNode('allow_edit')->defaultTrue()->end()
            ->scalarNode('http_adapter')->defaultValue('guzzle5')->end()
            ->enumNode('file_extension')->values(array('xlf', 'php'))->defaultValue('xlf')->end()
            ->arrayNode('locales')
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('domains')
                ->requiresAtLeastOneElement()
                ->prototype('scalar')->end()
            ->end()
            ->append($this->getProjectNode())
        ->end();

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getProjectNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('projects');
        $node
            ->isRequired()
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->scalarNode('api_key')->isRequired()->end()
                ->arrayNode('locales')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('domains')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}
