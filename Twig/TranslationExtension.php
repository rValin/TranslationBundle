<?php

namespace RValin\TranslationBundle\Twig;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
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

    private $_requiredRole;


    private $_authorizationChecker;

    /**
     * TranslationExtension constructor.
     *
     * @param TranslatorInterface $translator
     * @param RouterInterface     $router
     */
    public function __construct(TranslatorInterface $translator, RouterInterface $router, $useTextarea, $requiredRole, AuthorizationChecker $authorizationChecker)
    {
        $this->_translator = $translator;
        $this->_router = $router;
        $this->_useTextarea = $useTextarea;
        $this->_authorizationChecker = $authorizationChecker;
        $this->_requiredRole = $requiredRole;
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
        if(!$this->_authorizationChecker->isGranted($this->_requiredRole)) {
            return null;
        }

        try{
            $translations = $this->_translator->getUsedTranslations();
            $liveTranslation = $this->_translator->isLiveUpdate();
        }catch (\Exception $e)
        {
            return null;
        }

        return sprintf(
            "<script>rvalin_translation.init('%s', '%s', %s, %s);</script>",
            addcslashes(json_encode( $translations, JSON_HEX_APOS),'\\'),
            $this->_router->generate('r_valin_translation_update'),
            $this->_useTextarea ? 'true' : 'false',
            $liveTranslation ? 'true' : 'false'
        );

    }
}