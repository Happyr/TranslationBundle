<?php

namespace Happyr\LocoBundle\Controller;

use Happyr\LocoBundle\Model\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Tobias Nyholm
 * @author Damien Alexandre (damienalexandre)
 */
class ProfilerController extends Controller
{
    /**
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/flag", name="_profiler_flag_translations")
     * @Method("POST")
     *
     * @return Response
     */
    public function flagAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $profiler = $this->get('profiler');
        $profiler->disable();

        $messageId = $request->request->get('message_id');

        $profile = $profiler->loadProfile($token);
        $messages = $profile->getCollector('translation')->getMessages();
        $message = new Message($messages[$messageId]);

        $saved = $this->get('happyr.loco')->flagMessage($message);

        return new Response($saved ? 'OK' : 'ERROR');
    }
    /**
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/sync", name="_profiler_sync_translations")
     * @Method("POST")
     *
     * @return Response
     */
    public function syncAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $profiler = $this->get('profiler');
        $profiler->disable();

        $messageId = $request->request->get('message_id');

        $profile = $profiler->loadProfile($token);
        $messages = $profile->getCollector('translation')->getMessages();
        $message = new Message($messages[$messageId]);

        $translation = $this->get('happyr.loco')->fetchMessageFromLoco($message);

        if ($translation !== null) {
            return new Response($translation);
        }

        return new Response('Asset not found', 404);
    }

    /**
     * Save the selected translation to resources.
     *
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/save", name="_profiler_save_translations")
     * @Method("POST")
     *
     * @return Response
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
        $dataCollector = $profile->getCollector('translation');
        $toSave = array_intersect_key($dataCollector->getMessages(), array_flip($selected));

        $loco = $this->get('happyr.loco');
        $saved = $loco->createMessages($toSave);

        if ($saved > 0) {
            return new Response(sprintf('%s translation keys saved!', $saved));
        } else {
            return new Response("Can't save the translations.");
        }
    }
}
