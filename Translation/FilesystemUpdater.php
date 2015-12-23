<?php

namespace Happyr\TranslationBundle\Translation;

use Happyr\TranslationBundle\Model\Message;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm, Damien Harper
 *
 * Update the locale file system with changes in the catalogue
 */
class FilesystemUpdater
{
    /**
     * @var LoaderInterface loader
     */
    private $loader;

    /**
     * @var DumperInterface dumper
     */
    private $dumper;

    /**
     * @var string targetDir
     */
    private $targetDir;

    /**
     * @var string fileExtension
     */
    private $fileExtension;

    /**
     * @var Message[] messages
     */
    private $messages;

    /**
     * Filesystem constructor.
     *
     * @param LoaderInterface $loader
     * @param DumperInterface $dumper
     * @param                 $targetDir
     * @param                 $fileExtension
     */
    public function __construct(LoaderInterface $loader, DumperInterface $dumper, $targetDir, $fileExtension)
    {
        $this->loader = $loader;
        $this->dumper = $dumper;
        $this->targetDir = $targetDir;
        $this->messages = array();
        $this->fileExtension = $fileExtension;
    }

    /**
     * Returns translation file type.
     *
     * @return string
     */
    public function getFileExtension() {
        return $this->fileExtension;
    }

    /**
     * Returns translation dir.
     *
     * @return string
     */
    public function getTargetDir() {
        return $this->targetDir;
    }

    /**
     * Update message catalogues.
     *
     * @param Message[] $messages
     */
    public function updateMessageCatalog(array $messages)
    {
        $this->messages = array_merge($messages, $this->messages);
    }

    /**
     * Update the file system after the Response has been sent back to the client
     *
     * @param Event $event
     *
     * @throws \ErrorException
     * @throws \Exception
     */
    public function onTerminate(Event $event)
    {
        if (empty($this->messages)) {
            return;
        }

        /** @var MessageCatalogue[] $catalogues */
        $catalogues = array();
        foreach ($this->messages as $m) {
            $key = $m->getLocale().$m->getDomain();
            if (!isset($catalogues[$key])) {
                $file = sprintf('%s/%s.%s.%s', $this->targetDir, $m->getDomain(), $m->getLocale(), $this->getFileExtension());
                $catalogues[$key] = $this->loader->load($file, $m->getLocale(), $m->getDomain());
            }

            $translation = $m->getTranslation();
            if (empty($translation)) {
                $translation = sprintf('[%s]', $m->getId());
            }

            $catalogues[$key]->set($m->getId(), $translation, $m->getDomain());
        }

        foreach ($catalogues as $catalogue) {
            try {
                $this->dumper->dump($catalogue, ['path' => $this->targetDir]);
            } catch (\ErrorException $e) {
                // Could not save file
                // TODO better error handling
                throw $e;
            }
        }
    }
}
