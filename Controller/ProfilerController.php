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
 */
class ProfilerController extends Controller
{
    /**
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/edit", name="_profiler_translations_edit")
     *
     * @return Response
     */
    public function editAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $message = $this->getMessage($request, $token);
        $loco = $this->get('happyr.loco');

        if ($request->isMethod('GET')) {
            $loco->fetchTranslation($message);

            return $this->render('HappyrLocoBundle:Profiler:edit.html.twig', [
                'message' => $message,
                'key' => $request->query->get('message_id'),
            ]);
        }

        //Assert: This is a POST request
        $message->setTranslation($request->request->get('translation'));
        $loco->updateTranslation($message);

        return new Response($message->getTranslation());
    }

    /**
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/flag", name="_profiler_translations_flag")
     * @Method("POST")
     *
     * @return Response
     */
    public function flagAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $message = $this->getMessage($request, $token);

        $saved = $this->get('happyr.loco')->flagTranslation($message);

        return new Response($saved ? 'OK' : 'ERROR');
    }

    /**
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/sync", name="_profiler_translations_sync")
     * @Method("POST")
     *
     * @return Response
     */
    public function syncAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $message = $this->getMessage($request, $token);
        $translation = $this->get('happyr.loco')->fetchTranslation($message, true);

        if ($translation !== null) {
            return new Response($translation);
        }

        return new Response('Asset not found', 404);
    }

    /**
     * Save the selected translation to resources.
     *
     * @author Damien Alexandre (damienalexandre)
     *
     * @param Request $request
     * @param string  $token
     *
     * @Route("/{token}/translation/create-asset", name="_profiler_translations_create_assets")
     * @Method("POST")
     *
     * @return Response
     */
    public function createAssetsAction(Request $request, $token)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('_profiler', ['token' => $token]);
        }

        $messages = $this->getSelectedMessages($request, $token);
        if (empty($messages)) {
            return new Response('No key selected.');
        }

        $saved = $this->get('happyr.loco')->createAssets($messages);

        if ($saved > 0) {
            return new Response(sprintf('%s translation keys saved!', $saved));
        } else {
            return new Response("Can't save the translations.");
        }
    }

    /**
     * @param Request $request
     * @param string  $token
     *
     * @return Message
     */
    protected function getMessage(Request $request, $token)
    {
        $profiler = $this->get('profiler');
        $profiler->disable();

        $messageId = $request->request->get('message_id', $request->query->get('message_id'));

        $profile = $profiler->loadProfile($token);
        $messages = $profile->getCollector('translation')->getMessages();
        if (!isset($messages[$messageId])) {
            throw $this->createNotFoundException(sprintf('No message with key "%s" was found.', $messageId));
        }
        $message = new Message($messages[$messageId]);

        return $message;
    }


    /**
     * @param Request $request
     * @param string  $token
     *
     * @return array
     */
    protected function getSelectedMessages(Request $request, $token)
    {
        $profiler = $this->get('profiler');
        $profiler->disable();

        $selected = $request->request->get('selected');
        if (!$selected || count($selected) == 0) {
            return array();
        }

        $profile = $profiler->loadProfile($token);
        $dataCollector = $profile->getCollector('translation');
        $toSave = array_intersect_key($dataCollector->getMessages(), array_flip($selected));

        $messages = array();
        foreach ($toSave as $data) {
            //We do not want do add the placeholder to Loco. That messes up the stats.
            $data['translation']='';

            $messages[] = new Message($data);
        }

        return $messages;
    }


}
