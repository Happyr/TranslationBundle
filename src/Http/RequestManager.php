<?php

namespace Happyr\TranslationBundle\Http;

use Happyr\TranslationBundle\Exception\HttpException;
use Happyr\TranslationBundle\Translation\FilesystemUpdater;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Client\Plugin\ErrorPlugin;
use Http\Client\Plugin\LoggerPlugin;
use Http\Client\Plugin\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Psr\Log\LoggerInterface;

class RequestManager
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     *
     * @param HttpClient $client
     * @param MessageFactory $messageFactory
     */
    public function __construct(HttpClient $client, MessageFactory $messageFactory)
    {
        $this->client = new PluginClient($client, [new ErrorPlugin()]);
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function downloadFiles(FilesystemUpdater $filesystem, array $data)
    {
        $factory = $this->getMessageFactory();
        $client = $this->getClient();

        foreach ($data as $url => $fileName) {
            $response = $client->sendRequest($factory->createRequest('GET', $url));
            $filesystem->writeToFile($fileName, $response->getBody()->__toString());
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     *
     * @return array
     *
     * @throws HttpException
     */
    public function send($method, $url, $body = null, $headers = array())
    {
        $request = $this->getMessageFactory()->createRequest($method, $url, $headers, $body);

        try {
            $response = $this->getClient()->sendRequest($request);
        } catch (TransferException $e) {
            $message = 'Error sending request. ';
            if ($e instanceof \Http\Client\Exception\HttpException) {
                $message .= (string) $e->getResponse()->getBody();
            }

            throw new HttpException($message, $e->getCode(), $e);
        }

        // TODO add more error checks
        return json_decode($response->getBody()->__toString(), true);
    }

    /**
     * @return HttpClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     *
     * @return \Http\Message\MessageFactory
     */
    private function getMessageFactory()
    {
        return $this->messageFactory;
    }
}
