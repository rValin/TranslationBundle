<?php

namespace RValin\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class UpdatersPass implements CompilerPassInterface
{
    /**
     * Recupère l'ensemble des services taguer 'miller.admin'
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('rvalin.translation.updaters')) {
            return;
        }

        $definition = $container->findDefinition('rvalin.translation.updaters');

        $taggedServices = $container->findTaggedServiceIds('rvalin.translation.updater');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addUpdater',
                    [
                    new Reference($id),
                    $attributes['alias'],
                    ]
                );
            }
        }
    }
}
