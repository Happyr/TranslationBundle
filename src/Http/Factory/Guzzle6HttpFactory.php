<?php

namespace Happyr\TranslationBundle\Http\Factory;

use GuzzleHttp\ClientInterface;
use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;

class Guzzle6HttpFactory implements FactoryInterface
{
    /**
     * Guzzle6HttpFactory constructor.
     */
    public function __construct()
    {
        if (
            !class_exists(Client::class) ||
            !class_exists(GuzzleMessageFactory::class)
        ) {
            throw new \Exception("Guzzle6 not available - download with 'composer require php-http/guzzle6-adapter'");
        }
    }


    /**
     * @return ClientInterface
     * @throws \Exception
     */
    public function createClient()
    {
        return new Client();
    }

    /**
     * @return GuzzleMessageFactory
     */
    public function createMessageFactory()
    {
        return new GuzzleMessageFactory();
    }
}
