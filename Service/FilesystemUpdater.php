<?php

namespace Happyr\LocoBundle\Service;

use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm
 *
 * Update the locale file system with changes in the cataloge
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
     * Update message catalogues
     * @param $messages
     */
    public function updateMessageCatalog($messages)
    {
        /** @var MessageCatalogue[] $catalogues */
        $catalogues = array();
        foreach ($messages as $m) {
            $key = $m['locale'] . $m['domain'];
            if(!isset($catalogues[$key])) {
                $file = sprintf('%s/%s.%s.phps', $this->targetDir, $m['domain'], $m['locale']);
                $catalogues[$key] = $this->loader->load($file, $m['locale'], $m['domain']);
            }
            $catalogues[$key]->set($m['id'], '[Lorem Ipsum]', $m['domain']);
        }

        foreach ($catalogues as $catalogue) {
            $this->dumper->dump($catalogue, ['path'=>$this->targetDir]);
        }
    }
}