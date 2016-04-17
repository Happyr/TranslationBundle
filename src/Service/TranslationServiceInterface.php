<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Model\Message;

/**
 * @author Tobias Nyholm
 */
interface TranslationServiceInterface
{
    /**
     * Fetch a translation form Loco.
     *
     * @param Message $message
     */
    public function fetchTranslation(Message $message, $updateFs = false);

    /**
     * Update the translation in Loco.
     *
     * @param Message $message
     */
    public function updateTranslation(Message $message);

    /**
     * If there is something wrong with the translation, please flag it.
     *
     * @param Message $message
     * @param int     $type    0: Fuzzy, 1: Error, 2: Review, 3: Pending
     *
     * @return bool
     */
    public function flagTranslation(Message $message, $type = 0);

    /**
     * Create a new asset in Loco.
     *
     * @param Message $message
     *
     * @return bool
     */
    public function createAsset(Message $message);

    /**
     * Download all the translations from Loco. This will replace all the local files.
     * This is a quick method of getting all the latest translations and assets.
     */
    public function downloadAllTranslations();

    /**
     * Upload all the translations from the symfony project into. This will override
     * every strings in loco
     */
    public function uploadAllTranslations();

    /**
     * Synchronize all the translations with Loco. This will keep placeholders. This function is slower
     * than just to download the translations.
     */
    public function synchronizeAllTranslations();
}
