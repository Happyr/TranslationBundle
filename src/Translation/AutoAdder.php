<?php

namespace Happyr\TranslationBundle\Translation;

use Happyr\TranslationBundle\Model\Message;
use Happyr\TranslationBundle\Service\TranslationServiceInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * @author Tobias Nyholm
 */
class AutoAdder
{
    /**
     * @var DataCollectorTranslator
     */
    private $translator;

    /**
     * @var TranslationServiceInterface transService
     */
    private $transService;

    /**
     * @param DataCollectorTranslator     $translator
     * @param TranslationServiceInterface $transService
     * @param FilesystemUpdater           $fileSystemUpdater
     */
    public function __construct(DataCollectorTranslator $translator, TranslationServiceInterface $transService, FilesystemUpdater $fileSystemUpdater)
    {
        $this->translator = $translator;
        $this->transService = $transService;
        $this->fileSystemUpdater = $fileSystemUpdater;
    }

    public function onTerminate(Event $event)
    {
        if ($this->translator === null) {
            return;
        }

        $messages = $this->translator->getCollectedMessages();
        $created = array();
        foreach ($messages as $message) {
            if ($message['state'] === DataCollectorTranslator::MESSAGE_MISSING) {
                $m = new Message($message);
                $this->transService->createAsset($m);
                $created[] = $m;
            }
        }

        if (count($created) > 0) {
            // update filesystem
            $this->fileSystemUpdater->updateMessageCatalog($created);
        }
    }
}
