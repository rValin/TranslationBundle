<?php

namespace RValin\TranslationBundle\Updater;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Component\Translation\Writer\TranslationWriterInterface;

class FileUpdater implements UpdaterInterface
{
    /**
     * @var ContainerInterface
     */
    protected $_container;

    /**
     * @var KernelInterface
     */
    protected $_kernel;

    protected $_allowedBundles;

    protected $_dumpersConfig = [];

    /**
     * @var TranslationLoader
     */
    private $reader;

    /**
     * @var TranslationWriterInterface
     */
    private $_writer;

    public function __construct(KernelInterface $kernel, TranslationReaderInterface $reader, ContainerInterface $container, $allowedBundles, $dumpersConfig, TranslationWriterInterface $writer)
    {
        $this->_kernel = $kernel;
        $this->_container = $container;
        $this->reader = $reader;
        $this->_allowedBundles = $allowedBundles;
        $this->_dumpersConfig = $dumpersConfig;
        $this->_writer = $writer;
    }

    public function update($key, $translation, $domain, $locale)
    {
        $catalogues = $this->getCatalogues($key, $domain, $locale);

        foreach ($catalogues as $catalogue) {
            $catalogue->set($key, $translation, $domain);
            foreach ($catalogue->getResources() as $resource) {
                $regex  = '^.*\/([a-zA-Z0-9-_]+).' . $locale . '.([a-z]+)$';
                preg_match('#' . $regex . '#', $resource->getResource(), $matches);

                if ($matches[1] === $domain) {
                    $this->updateFile($resource->getResource(), $catalogue, $domain, $matches[2]);
                }
            }
        }

        $this->_container->get('rvalin.translation.translator')->removeLocalesCacheFiles([$locale]);
    }

    /**
     * @param                  $file
     * @param MessageCatalogue $catalogue
     * @param                  $domain
     * @param                  $extension
     */
    public function updateFile($file, MessageCatalogue $catalogue, $domain, $extension)
    {
        $options = [];
        if (array_key_exists($extension, $this->_dumpersConfig)) {
            $options = $this->_dumpersConfig[$extension];
        }

        $options['path'] = dirname($file);
        
        $this->_writer->write($catalogue, $extension, $options);
    }

    /**
     * @param $key
     * @param $domain
     * @param $locale
     *
     * @return MessageCatalogue[]
     */
    protected function getCatalogues($key, $domain, $locale)
    {
        $catalogues = $this->getCataloguePerBundle($locale);
        $validCatalogues = [];
        foreach ($catalogues as $catalogue) {
            if ($catalogue->has($key, $domain)) {
                $validCatalogues[] = $catalogue;
            }
        }

        return $validCatalogues;
    }

    /**
     * @param $locale
     *
     * @return MessageCatalogue[]
     */
    protected function getCataloguePerBundle($locale)
    {
        $catalogues = [$this->loadCurrentMessages($locale, [$this->_kernel->getProjectDir() . '/translations'])];

        return $catalogues;
    }


    /**
     * @param string $locale
     * @param array  $transPaths
     *
     * @return MessageCatalogue
     */
    private function loadCurrentMessages($locale, $transPaths)
    {
        $currentCatalogue = new MessageCatalogue($locale);
        foreach ($transPaths as $path) {
            if (is_dir($path)) {
                $this->reader->read($path, $currentCatalogue);
            }
        }

        return $currentCatalogue;
    }
}
