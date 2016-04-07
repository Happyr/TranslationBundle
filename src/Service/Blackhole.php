<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Model\Message;

class Blackhole implements TranslationServiceInterface
{
    /**
     * @inheritDoc
     */
    public function fetchTranslation(Message $message, $updateFs = false)
    {
    }

    /**
     * @inheritDoc
     */
    public function updateTranslation(Message $message)
    {
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
    public function importAllTranslations()
    {
    }

    /**
     * @inheritDoc
     */
    public function synchronizeAllTranslations()
    {
    }

}