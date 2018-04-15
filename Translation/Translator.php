<?php
namespace RValin\TranslationBundle\Translation;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface
{
    const SESSION_ATTR = 'translation_live_update';

    /**
     * List of translations used
     * @var array
     */
    private $used_translations = [];

    /**
     * @var bool
     */
    private $live_update = false;

    /**
     * @var array
     */
    private $allowed_domains;

    /**
     * @var TranslatorInterface
     */
    protected $_translator;

    public function __construct(TranslatorInterface $translator, $allowedDomains)
    {
        $this->_translator = $translator;
        $this->allowed_domains = $allowedDomains;
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
    private function registerTranslation($translationCode, $translationValue, $key, $parameters, $domain, $locale, $number = null, $plural = false) {
        $id = count($this->used_translations);
        $this->used_translations[$id] = [
            'key' => $key,
            'translationCode' => $translationCode,
            'translationValue' => $translationValue,
            'parameters' => $parameters,
            'number' => $number,
            'domain' => ($domain === null) ? 'messages' : $domain,
            'locale' => ($locale === null) ? $this->getLocale() : $locale,
            'plural' => $plural,
        ];
        return '||'.$id.'||'.$translationValue.'||||';
    }

    public function getUsedTranslations(){
        return $this->used_translations;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        if ($this->isLiveUpdate() && $this->isAllowedDomain($domain)) {
            if(null === $locale) {
                $locale = $this->getLocale();
            }

            if(null === $domain) {
                $domain = 'messages';
            }


            if($this->getCatalogue($locale)->has($id, $domain))
            {
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
        if ($this->live_update && $this->isAllowedDomain($domain)) {
            if(null === $locale) {
                $locale = $this->getLocale();
            }

            if(null === $domain) {
                $domain = 'messages';
            }


            if($this->getCatalogue($locale)->has($id, $domain)) {
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
        return (empty($this->allowed_domains) || \in_array($domain, $this->allowed_domains, true));
    }

    /**
     * @return bool
     */
    public function isLiveUpdate()
    {
        return $this->live_update;
    }

    public function getLocale()
    {
        return $this->_translator->getLocale();
    }

    public function setLocale($locale)
    {
        $this->_translator->setLocale($locale);
    }

    public function setLiveUpdate($liveUpdate) {
        $this->live_update = $liveUpdate;
    }

    public function getCatalogue($locale = null)
    {
        return $this->_translator->getCatalogue($locale);
    }
}