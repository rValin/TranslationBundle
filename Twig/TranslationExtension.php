<?php

namespace RValin\TranslationBundle\Twig;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationExtension extends \Twig_Extension
{
    /**
     * @var Translator
     */
    private $_translator;

    /**
     * @var RouterInterface
     */
    private $_router;

    /**
     * @var bool
     */
    private $_useTextarea;

    /**
     * TranslationExtension constructor.
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     */
    public function __construct(TranslatorInterface $translator, RouterInterface $router, $useTextarea)
    {
        $this->_translator = $translator;
        $this->_router = $router;
        $this->_useTextarea = $useTextarea;
    }

    /**
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions() {
        return [
            'translationList' => new \Twig_SimpleFunction('translationList', [$this, 'translationList'], ['is_safe' => ['html']]),
        ];
    }


    /**
     * @return null|string
     */
    public function translationList()
    {
        try{
            $translations = $this->_translator->getUsedTranslations();
        }catch (\Exception $e)
        {

            return null;
        }

        return sprintf(
            "<script>rvalin_translation.init('%s', '%s', %s);</script>",
            json_encode($translations),
            $this->_router->generate('r_valin_translation_update'),
            ($this->_useTextarea) ? 'true' : 'false'
        );
    }
}