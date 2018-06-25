<?php

namespace RValin\TranslationBundle\Updater;

interface UpdaterInterface
{
    /**
     * Update a translation
     * @param $key
     * @param $translation
     * @param $domain
     * @param $locale
     *
     * @return mixed
     */
    public function update($key, $translation, $domain, $locale);
}
