<?php

namespace RValin\TranslationBundle\Updater;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitManager;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;
use RValin\TranslationBundle\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class LexikTranslationUpdater implements UpdaterInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var TransUnitManager
     */
    protected $transUnitManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * LexikTranslationUpdater constructor.
     *
     * @param StorageInterface $storage
     * @param TransUnitManager $transUnitManager
     */
    public function __construct(StorageInterface $storage, TransUnitManager $transUnitManager, TranslatorInterface $translator)
    {
        $this->storage = $storage;
        $this->transUnitManager = $transUnitManager;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function update($key, $translation, $domain, $locale)
    {
        $transUnit = $this->storage->getTransUnitByKeyAndDomain($key, $domain);
        $this->transUnitManager->updateTranslation($transUnit, $locale, $translation, true);
        $this->translator->removeLocalesCacheFiles([$locale]);
    }
}
