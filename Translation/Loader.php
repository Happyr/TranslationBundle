<?php

namespace Happyr\LocoBundle\LocoBundle\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @author Cliff Odijk (cmodijk)
 */
class Loader extends ArrayLoader implements LoaderInterface
{
    public function load($resource, $locale, $domain = "messages")
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf("This is not a local file '%s'.", $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf("File '%s' not found.", $resource));
        }

        $messages = require($resource);

        $messages = array_filter($messages);
        array_walk($messages, function(&$param) {
            $param = stripslashes(str_replace(array("\\|", "\\\\|"), "|", $param));
        });

        $catalogue = parent::load($messages, $locale, $domain);
        $catalogue->addResource(new FileResource($resource));

        return $catalogue;
    }
}