<?php

namespace RValin\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('r_valin_translation');
        $rootNode->children()
            ->arrayNode('dumpers_config')
                ->arrayPrototype()
                    ->variablePrototype()->end()
                ->end()
            ->end()
            ->arrayNode('allowed_bundles')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_domains')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('updaters')
                ->defaultValue(['file'])
                ->scalarPrototype()->end()
            ->end()
            ->scalarNode('role')
                ->defaultValue('ROLE_UPDATE_TRANSLATION')
            ->end()
            ->arrayNode('edit')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('content_editable')
                        ->defaultTrue()
                    ->end()
                    ->scalarNode('textarea')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
