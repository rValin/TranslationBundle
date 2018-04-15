<?php

namespace RValin\TranslationBundle\Listener;

use RValin\TranslationBundle\Translation\Translator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationKernelListener
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $contentEditable;

    private $requiredRole;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecked;

    /**
     * TranslatorListener constructor.
     * @param $translator
     */
    public function __construct($translator, $contentEditable = false, $requiredRole, AuthorizationCheckerInterface $authChecker)
    {
        $this->translator = $translator;
        $this->contentEditable = $contentEditable;
        $this->requiredRole = $requiredRole;
        $this->authChecked = $authChecker;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $session = $event->getRequest()->getSession();
        if (!$this->authChecked->isGranted($this->requiredRole)) {
            $session->remove(Translator::SESSION_ATTR);
            try {
                $this->translator->setLiveUpdate(false);
            } catch (\Exception $exception) {}
            return;
        }


        $updateTranslation = $event->getRequest()->query->get('update_translation');

        if (null !== $updateTranslation) {
            $session->set(Translator::SESSION_ATTR, $updateTranslation == 1);
        }

        try {
            $this->translator->setLiveUpdate($session->get(Translator::SESSION_ATTR, false));
            $this->translator->setCanUpdate(true);
        } catch (\Exception $exception) {}
    }

    /**
     * @param FilterResponseEvent $
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->authChecked->isGranted($this->requiredRole)) {
            return;
        }

        try{
            if (!$this->translator->isLiveUpdate()) {
                return;
            }
        }catch (\Exception $e)
        {
            return;
        }

        $html = $event->getResponse()->getContent();
        $count = 0;

        // convert all translation in html tag
        do {
            $html = preg_replace('#<([^<>]*)\|\|([0-9]+)\|\|([\s\S]*)\|\|\|\|([^<>]*)>#U', '<$1$3$4>', $html, -1, $count);
        } while ($count > 0);

        // convert all translation in head
        preg_match('#<head>([\s\S]+)</head>#', $html, $headContent);
        $headContent = end($headContent);
        do {
            $headContent = preg_replace('#\|\|([0-9]+)\|\|([\s\S]*)\|\|\|\|#U', '$2', $headContent, -1, $count);
        } while ($count > 0);
        $html = preg_replace('#<head>([\s\S]+)</head>#', '<head>'.$headContent.'</head>', $html);

        // replace all translation with <trans> tag

        $contentEditable = '';
        if($this->contentEditable) {
            $contentEditable = 'contentEditable="true"';
        }
        $html = preg_replace('#\|\|([0-9]+)\|\|([.\S\s]*)\|\|\|\|#U', '<trans title="update this text" data-id="$1" '.$contentEditable.'>$2</trans>', $html);
        $event->getResponse()->setContent($html);
    }
}