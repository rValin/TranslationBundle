<?php

namespace RValin\TranslationBundle\DependencyInjection\Compiler;

use RValin\TranslationBundle\Translation\Translator;
use RValin\TranslationBundle\Updater\Updaters;
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
        if (!$container->has(Updaters::class)) {
            return;
        }


        $definition = $container->findDefinition(Updaters::class);

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

        $translator = $container->getDefinition(Translator::class)->setArguments([
            new Reference($container->getParameterBag()->get('rvalin_translation.translator_service')),
            new Reference('parameter_bag'),
        ]);

//        $container->getDefinition('translator')->setClass(Translator::class);
    }
}
