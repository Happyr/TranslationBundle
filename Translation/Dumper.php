<?php

namespace Happyr\LocoBundle\LocoBundle\Translation;

use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm
 */
class Dumper extends FileDumper
{
    protected function format(MessageCatalogue $messages, $domain = 'messages')
    {
        $output = '';

        foreach ($messages->all($domain) as $source => $target) {
            $output .= sprintf("(%s)(%s)\n", $source, $target);
        }

        return $output;
    }

    protected function getExtension()
    {
        return 'phps';
    }
}