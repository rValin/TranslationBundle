<?php
namespace RValin\TranslationBundle\Translation;

use Lexik\Bundle\TranslationBundle\Translation\Translator as BaseTranslator;

class LexikTranslator extends BaseTranslator
{
    /**
     * List of translations used
     * @var array
     */
    private $used_translations = [];

    /**
     * @var bool
     */
    private $live_update = true;

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
        if($this->isLiveUpdate())
        {
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

        return $translationValue;
    }

    public function getUsedTranslations(){
        return $this->used_translations;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->registerTranslation(
            parent::trans($id, [], $domain, $locale),
            parent::trans($id, $parameters, $domain, $locale),
            $id,
            $parameters,
            $domain,
            $locale
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->registerTranslation(
            parent::trans($id, [], $domain, $locale),
            parent::transChoice($id, $number, $parameters, $domain, $locale),
            $id,
            $parameters,
            $domain,
            $locale,
            $number,
            true
        );
    }

    /**
     * @return bool
     */
    public function isLiveUpdate()
    {
        return $this->live_update;
    }
}