<?php

namespace RValin\TranslationBundle;

use RValin\TranslationBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use RValin\TranslationBundle\DependencyInjection\Compiler\UpdatersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RValinTranslationBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new UpdatersPass());
    }
}
