<?php
namespace RValin\TranslationBundle\DependencyInjection\Compiler;

use RValin\TranslationBundle\Translation\LexikTranslator;
use RValin\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('translator.default');
        $definition->setClass(Translator::class);

        if ($container->has('lexik_translation.translator')) {
            $definitionLexik = $container->getDefinition('lexik_translation.translator');
            $definitionLexik->setClass(LexikTranslator::class);
        }
    }
}