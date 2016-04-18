<?php

namespace Happyr\TranslationBundle\Http\Factory;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;

interface FactoryInterface
{
    /**
     * @return HttpClient
     */
    public function createClient();

    /**
     * @return MessageFactory
     */
    public function createMessageFactory();
}
