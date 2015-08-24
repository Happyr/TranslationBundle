<?php

namespace Happyr\TranslationBundle\Translation;

use Happyr\TranslationBundle\Model\Message;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm
 *
 * Update the locale file system with changes in the catalogue
 */
class FilesystemUpdater
{
    const FILE_EXTENSION = 'phps';

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
     * Filesystem constructor.
     *
     * @param LoaderInterface $loader
     * @param DumperInterface $dumper
     * @param                 $targetDir
     */
    public function __construct(LoaderInterface $loader, DumperInterface $dumper, $targetDir)
    {
        $this->loader = $loader;
        $this->dumper = $dumper;
        $this->targetDir = $targetDir;
    }

    /**
     * Update message catalogues.
     *
     * @param Message[] $messages
     */
    public function updateMessageCatalog(array $messages)
    {
        /** @var MessageCatalogue[] $catalogues */
        $catalogues = array();
        foreach ($messages as $m) {
            $key = $m->getLocale().$m->getDomain();
            if (!isset($catalogues[$key])) {
                $file = sprintf('%s/%s.%s.%s', $this->targetDir, $m->getDomain(), $m->getLocale(), self::FILE_EXTENSION);
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
