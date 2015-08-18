<?php

namespace Happyr\LocoBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Damien Alexandre (damienalexandre)
 */
class ProfilerController extends Controller
{
    /**
     * Save the selected translation to resources.
     *
     * @Route("/{token}/translation/save", name="_profiler_save_translations")
     *
     * @return Response A Response instance
     */
    public function saveAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $profiler = $this->get('profiler');
        $profiler->disable();

        $selected = $request->request->get('selected');
        if (!$selected || count($selected) == 0) {
            return new Response('No key selected.');
        }

        $profile = $profiler->loadProfile($token);
        $all = $profile->getCollector('translation');
        $toSave = array_intersect_key($all->getMessages(), array_flip($selected));

        $loco = $this->get('happyr.loco');
        $saved = $loco->createMessages($toSave);

        if ($saved > 0) {
            return new Response(sprintf('%s translation keys saved!', $saved));
        } else {
            return new Response("Can't save the translations.");
        }
    }
}
