<?php

namespace RValin\TranslationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RValinTranslationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('rvalin_translation.dumpers_config', $config['dumpers_config']);
        $container->setParameter('rvalin_translation.allowed_bundles', $config['allowed_bundles']);
        $container->setParameter('rvalin_translation.updaters', $config['updaters']);
        $container->setParameter('rvalin_translation.edit.content_editable', $config['edit']['content_editable']);
        $container->setParameter('rvalin_translation.edit.textarea', $config['edit']['textarea']);
        $container->setParameter('rvalin_translation.allowed_domains', $config['allowed_domains']);
        $container->setParameter('rvalin_translation.role', $config['role']);

        $definition = $container->findDefinition('rvalin.translation.translator');
        $definition->replaceArgument(0, new Reference($config['translator_service']));

        $container->setAlias('translator', 'rvalin.translation.translator');
    }
}
