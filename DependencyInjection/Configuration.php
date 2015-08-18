<?php

namespace Happyr\LocoBundle\DependencyInjection;

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
        $root = $treeBuilder->root('happyr_loco');

        $root->children()
            ->scalarNode("target_dir")->defaultValue("%kernel.root_dir%/Resources/translations")->end()
            ->booleanNode("use_domain_as_tag")->defaultFalse->end()
            ->arrayNode('locales')->end()
            ->arrayNode('domains')->end()
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
                ->arrayNode('locales')->end()
                ->arrayNode('domains')->end()
            ->end()
        ->end();

        return $node;
    }
}
