<?php

namespace RValin\TranslationBundle\Updater;

class Updaters
{
    private $updaters = [];

    public function addUpdater(UpdaterInterface $updater, $alias)
    {
        $this->updaters[$alias] = $updater;
    }

    public function getUpdater($name)
    {
        if(!array_key_exists($name, $this->updaters))
        {
            throw new \Exception(sprintf('Updater named %s was not found', $name));
        }

        return $this->updaters[$name];
    }
}