<?php

namespace RValin\TranslationBundle\Listener;

use RValin\TranslationBundle\Translation\Translator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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

    /**
     * TranslatorListener constructor.
     * @param $translator
     */
    public function __construct($translator, $contentEditable = false)
    {
        $this->translator = $translator;
        $this->contentEditable = $contentEditable;
    }

    /**
     * @param FilterResponseEvent $
     */
    public function onKernelResponse(FilterResponseEvent $event) {
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
            $html = preg_replace('#<([^<>]*)\|\|([0-9]+)\|\|(.*)\|\|\|\|([^<>]*)>#', '<$1$3$4>', $html, -1, $count);
        } while ($count > 0);

        // convert all translation in head
        preg_match('#<head>([\s\S]+)</head>#', $html, $headContent);
        $headContent = end($headContent);
        do {
            $headContent = preg_replace('#\|\|([0-9]+)\|\|(.*)\|\|\|\|#', '$2', $headContent, -1, $count);
        } while ($count > 0);
        $html = preg_replace('#<head>([\s\S]+)</head>#', '<head>'.$headContent.'</head>', $html);

        // replace all translation with <trans> tag

        $contentEditable = '';
        if($this->contentEditable) {
            $contentEditable = 'contentEditable="true"';
        }
        $html = preg_replace('#\|\|([0-9]+)\|\|(.*)\|\|\|\|#', '<trans data-id="$1" '.$contentEditable.'>$2</trans>', $html);
        $event->getResponse()->setContent($html);
    }
}