<?php

namespace Happyr\LocoBundle\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm
 * @author Cliff Odijk (cmodijk)
 */
class Loader implements LoaderInterface
{
    public function load($resource, $locale, $domain = 'messages')
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf("This is not a local file '%s'.", $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf("File '%s' not found.", $resource));
        }

        $messages = require $resource;
        $catalogue = new MessageCatalogue($locale);
        $catalogue->add($messages, $domain);

        $catalogue->addResource(new FileResource($resource));

        return $catalogue;
    }
}
