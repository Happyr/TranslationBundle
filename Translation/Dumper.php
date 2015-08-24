<?php

namespace Happyr\TranslationBundle\Translation;

use Happyr\TranslationBundle\Service\FilesystemUpdater;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm
 */
class Dumper extends FileDumper
{
    /**
     * {@inheritdoc}
     */
    protected function format(MessageCatalogue $messages, $domain)
    {
        $output = "<?php\n\nreturn ".var_export($messages->all($domain), true).";\n";

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension()
    {
        return FilesystemUpdater::FILE_EXTENSION;
    }
}
