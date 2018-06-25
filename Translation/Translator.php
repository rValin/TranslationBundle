<?php
namespace RValin\TranslationBundle\Translation;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface
{

    protected $cacheDir;

    const SESSION_ATTR = 'translation_live_update';
    const DEFAULT_ROLE = 'ROLE_UPDATE_TRANSLATION';

    /**
     * List of translations used
     * @var array
     */
    private $usedTranslations = [];

    /**
     * @var bool
     */
    private $liveUpdate = false;

    /**
     * @var array
     */
    private $allowedDomains;

    /**
     * @var TranslatorInterface
     */
    protected $_translator;

    public function __construct(TranslatorInterface $translator, $allowedDomains, $cacheDir)
    {
        $this->_translator = $translator;
        $this->allowedDomains = $allowedDomains;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Add a translation to the list of translations used
     *
     * @param      $translationCode
     * @param      $translationValue
     * @param      $key
     * @param      $parameters
     * @param      $domain
     * @param      $locale
     * @param null $number
     *
     * @return string
     */
    private function registerTranslation($translationCode, $translationValue, $key, $parameters, $domain, $locale, $number = null, $plural = false)
    {
        $id = count($this->usedTranslations);
        $this->usedTranslations[$id] = [
            'key' => $key,
            'translationCode' => $translationCode,
            'translationValue' => $translationValue,
            'parameters' => $parameters,
            'number' => $number,
            'domain' => ($domain === null) ? 'messages' : $domain,
            'locale' => ($locale === null) ? $this->getLocale() : $locale,
            'plural' => $plural,
            'isVisible' => true,
        ];
        return '||' . $id . '||' . $translationValue . '||||';
    }

    public function getUsedTranslations()
    {
        return $this->usedTranslations;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if ($this->isLiveUpdate() && $this->isAllowedDomain($domain)) {
            if (null === $locale) {
                $locale = $this->getLocale();
            }

            if (null === $domain) {
                $domain = 'messages';
            }

            if ($this->getCatalogue($locale)->has($id, $domain)) {
                return $this->registerTranslation(
                    $this->_translator->trans($id, [], $domain, $locale),
                    $this->_translator->trans($id, $parameters, $domain, $locale),
                    $id,
                    $parameters,
                    $domain,
                    $locale
                );
            }
        }

        return $this->_translator->trans($id, $parameters, $domain, $locale);
    }


    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = 'messages', $locale = null)
    {
        if ($this->liveUpdate && $this->isAllowedDomain($domain)) {
            if (null === $locale) {
                $locale = $this->getLocale();
            }

            if (null === $domain) {
                $domain = 'messages';
            }

            if ($this->getCatalogue($locale)->has($id, $domain)) {
                return $this->registerTranslation(
                    $this->_translator->trans($id, [], $domain, $locale),
                    $this->_translator->transChoice($id, $number, $parameters, $domain, $locale),
                    $id,
                    $parameters,
                    $domain,
                    $locale,
                    $number,
                    true
                );
            }
        }

        return $this->_translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    protected function isAllowedDomain($domain)
    {
        return (empty($this->allowedDomains) || \in_array($domain, $this->allowedDomains, true));
    }

    /**
     * @return bool
     */
    public function isLiveUpdate()
    {
        return $this->liveUpdate;
    }

    public function getLocale()
    {
        return $this->_translator->getLocale();
    }

    public function setLocale($locale)
    {
        $this->_translator->setLocale($locale);
    }

    public function setLiveUpdate($liveUpdate)
    {
        $this->liveUpdate = $liveUpdate;
    }

    public function getCatalogue($locale = null)
    {
        return $this->_translator->getCatalogue($locale);
    }

    /**
     * Remove the cache file corresponding to the given locale.
     *
     * @param string $locale
     * @return boolean
     */
    public function removeCacheFile($locale)
    {
        $localeExploded = explode('_', $locale);
        $finder = new Finder();
        $finder->files()->in($this->cacheDir)->name(sprintf('/catalogue\.%s.*\.php$/', $localeExploded[0]));
        $deleted = true;
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $this->invalidateSystemCacheForFile($path);
            $deleted = unlink($path);

            $metadata = $path . '.meta';
            if (file_exists($metadata)) {
                $this->invalidateSystemCacheForFile($metadata);
                unlink($metadata);
            }
        }

        return $deleted;
    }

    /**
     * Remove the cache file corresponding to each given locale.
     *
     * @param array $locales
     */
    public function removeLocalesCacheFiles($locales)
    {
        foreach ($locales as $locale) {
            $this->removeCacheFile($locale);
        }

        // also remove database.resources.php cache file
        $file = sprintf('%s/database.resources.php', $this->cacheDir);
        if (file_exists($file)) {
            $this->invalidateSystemCacheForFile($file);
            unlink($file);
        }

        $metadata = $file . '.meta';
        if (file_exists($metadata)) {
            $this->invalidateSystemCacheForFile($metadata);
            unlink($metadata);
        }
    }

    /**
     * @param string $path
     *
     * @throws \RuntimeException
     */
    protected function invalidateSystemCacheForFile($path)
    {
        if (ini_get('apc.enabled') && function_exists('apc_delete_file')) {
            if (apc_exists($path) && !apc_delete_file($path)) {
                throw new \RuntimeException(sprintf('Failed to clear APC Cache for file %s', $path));
            }
        } elseif ('cli' === php_sapi_name() ? ini_get('opcache.enable_cli') : ini_get('opcache.enable')) {
            if (function_exists("opcache_invalidate") && !opcache_invalidate($path, true)) {
                throw new \RuntimeException(sprintf('Failed to clear OPCache for file %s', $path));
            }
        }
    }
}
