<?php

namespace RValin\TranslationBundle\Updater;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;

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
     * @var TranslationReaderInterface
     */
    private $reader;

    public function __construct(KernelInterface $kernel, TranslationReaderInterface $reader, ContainerInterface $container, $allowedBundles, $dumpersConfig)
    {
        $this->_kernel = $kernel;
        $this->_container = $container;
        $this->reader = $reader;

        dump($allowedBundles, $dumpersConfig);
        $this->_allowedBundles = $allowedBundles;
        $this->_dumpersConfig = $dumpersConfig;
    }

    public function update($key, $translation, $domain, $locale)
    {
        $catalogues = $this->getCatalogues($key, $domain, $locale);

        foreach($catalogues as $catalogue)
        {
            $catalogue->set($key, $translation, $domain);
            foreach ($catalogue->getResources() as $resource)
            {
                $regex  = '^.*\/([a-zA-Z0-9-_]+).'.$locale.'.([a-z]+)$';
                preg_match('#'.$regex.'#', $resource->getResource(), $matches);

                if($matches[1] === $domain) {
                    $this->updateFile($resource->getResource(), $catalogue, $domain, $matches[2]);
                }
            }
        }
    }

    /**
     * @param                  $file
     * @param MessageCatalogue $catalogue
     * @param                  $domain
     * @param                  $extension
     */
    public function updateFile($file, MessageCatalogue $catalogue, $domain, $extension)
    {
        $dumper = $this->_container->get('translation.dumper.'.$extension);

        if(!$dumper instanceof FileDumper)
        {
            throw new \InvalidArgumentException('$dumper should be an instance of '.FileDumper::class);
        }

        $options = [];
        if (array_key_exists($extension, $this->_dumpersConfig)) {
            $options = $this->_dumpersConfig[$extension];
        }

        dump($options);
        file_put_contents($file, $dumper->formatCatalogue($catalogue, $domain, $options));
    }

    /**
     * @param $key
     * @param $domain
     * @param $locale
     *
     * @return MessageCatalogue[]
     */
    protected function getCatalogues($key, $domain, $locale) {
        $catalogues = $this->getCataloguePerBundle($locale);
        $validCatalogues = [];
        foreach ($catalogues as $catalogue)
        {
            if($catalogue->has($key, $domain))
            {
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
        $catalogues = [$this->loadCurrentMessages($locale, [$this->_kernel->getRootDir().'/Resources/translations'])];

        foreach ($this->_kernel->getBundles() as $bundle)
        {
            if (!empty($this->_allowedBundles) && \in_array($bundle->getName(), $this->_allowedBundles)) {
                continue;
            }

            $catalogues[] = $this->loadCurrentMessages($locale, [$bundle->getPath().'/Resources/translations']);
        }

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