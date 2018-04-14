<?php

namespace RValin\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function updateAction(Request $request)
    {
        $updaters = $this->get('rvalin.translation.updaters');
        foreach($this->getParameter('rvalin_translation.updaters') as $updaterName)
        {
            $updater = $updaters->getUpdater($updaterName);

            $updater->update(
                $request->request->get('key'),
                $request->request->get('translationCode'),
                $request->request->get('domain'),
                $request->request->get('locale')
            );
        }

        return new JsonResponse(['responseCode' => 200]);
    }
}
