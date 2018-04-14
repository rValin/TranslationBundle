<?php

namespace RValin\TranslationBundle\Updater;

use Lexik\Bundle\TranslationBundle\Manager\TransUnitManager;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

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
     * LexikTranslationUpdater constructor.
     *
     * @param StorageInterface $storage
     * @param TransUnitManager $transUnitManager
     */
    public function __construct(StorageInterface $storage, TransUnitManager $transUnitManager)
    {
        $this->storage = $storage;
        $this->transUnitManager = $transUnitManager;
    }

    /**
     * @inheritdoc
     */
    public function update($key, $translation, $domain, $locale)
    {
        $transUnit = $this->storage->getTransUnitByKeyAndDomain($key, $domain);
        $this->transUnitManager->updateTranslation($transUnit, $locale, $translation, true);
    }
}