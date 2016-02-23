<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Model\Message;
use Happyr\TranslationBundle\Translation\FilesystemUpdater;

class Filesystem implements TranslationServiceInterface
{
    /**
     * @var FilesystemUpdater filesystemService
     */
    private $filesystemService;

    /**
     *
     * @param FilesystemUpdater $filesystemService
     */
    public function __construct(FilesystemUpdater $filesystemService)
    {
        $this->filesystemService = $filesystemService;
    }

    /**
     * @inheritDoc
     */
    public function fetchTranslation(Message $message, $updateFs = false)
    {
        if ($updateFs) {
            $this->filesystemService->updateMessageCatalog([$message]);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateTranslation(Message $message)
    {
        $this->filesystemService->updateMessageCatalog([$message]);
    }

    /**
     * @inheritDoc
     */
    public function flagTranslation(Message $message, $type = 0)
    {
    }

    /**
     * @inheritDoc
     */
    public function createAsset(Message $message)
    {
    }

    /**
     * @inheritDoc
     */
    public function downloadAllTranslations()
    {
    }

    /**
     * @inheritDoc
     */
    public function synchronizeAllTranslations()
    {
    }

}