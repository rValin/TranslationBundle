<?php

namespace RValin\TranslationBundle\Twig;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
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
    public function __construct(TranslatorInterface $translator, RouterInterface $router, ContainerBagInterface $parameterBag, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->_translator = $translator;
        $this->_router = $router;
        $this->_useTextarea = $parameterBag->get('rvalin_translation.edit.textarea');
        $this->_authorizationChecker = $authorizationChecker;
        $this->_requiredRole = $parameterBag->get('rvalin_translation.role');
    }

    /**
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            'translationList' => new \Twig_SimpleFunction('translationList', [$this, 'translationList'], ['is_safe' => ['html']]),
            'translationLiveEditIsEnabled' => new \Twig_SimpleFunction('translationLiveEditIsEnabled', [$this, 'translationLiveEditIsEnabled']),
        ];
    }

    public function translationLiveEditIsEnabled()
    {
        if (!$this->_authorizationChecker->isGranted($this->_requiredRole)) {
            return false;
        }

        try {
            return $this->_translator->isLiveUpdate();
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @return null|string
     */
    public function translationList()
    {
        try {
            if (!$this->_authorizationChecker->isGranted($this->_requiredRole)) {
                return null;
            }

            $translations = $this->_translator->getUsedTranslations();
            $liveTranslation = $this->_translator->isLiveUpdate();
        } catch (\Exception $e) {
            return null;
        }

        return sprintf(
            "<script>rvalin_translation.init('%s', '%s', %s, %s);</script>",
            addcslashes(json_encode($translations, JSON_HEX_APOS), '\\'),
            $this->_router->generate('r_valin_translation_update'),
            $this->_useTextarea ? 'true' : 'false',
            $liveTranslation ? 'true' : 'false'
        );
    }
}
