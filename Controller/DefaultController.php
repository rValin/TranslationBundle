<?php

namespace RValin\TranslationBundle\Controller;

use RValin\TranslationBundle\Updater\Updaters;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @param Request  $request
     * @param Updaters $updaters
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateAction(Request $request, Updaters $updaters)
    {
        if (!$this->isGranted($this->getParameter('rvalin_translation.role'))) {
            throw $this->createAccessDeniedException();
        }

        $updaterNames = $this->getParameter('rvalin_translation.updaters');
        if (empty($updaterNames)) {
            throw new \InvalidArgumentException('No updater selected');
        }

        $key = $request->request->get('key');
        $translationCode = $request->request->get('translationCode');
        $domain = $request->request->get('domain');
        $locale = $request->request->get('locale');

        foreach ($updaterNames as $updaterName) {
            $updater = $updaters->getUpdater($updaterName);
            $updater->update($key, $translationCode, $domain, $locale);
        }

        return new JsonResponse(['responseCode' => 200]);
    }
}
