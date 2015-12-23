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
use Psr\Log\LoggerInterface;

class RequestManager
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function downloadFiles(FilesystemUpdater $filesystem, array $data)
    {
        $factory = MessageFactoryDiscovery::find();
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
        $request = MessageFactoryDiscovery::find()->createRequest($method, $url, $headers, $body);

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
     * Return the client. If no client exist, create a new one filled with plugins.
     *
     * @return HttpClient
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $plugins = array();

            if ($this->logger) {
                $plugins[] = new LoggerPlugin($this->logger);
            }

            $plugins[] = new ErrorPlugin();

            $this->client = new PluginClient(HttpClientDiscovery::find(), $plugins);
        }

        return $this->client;
    }

    /**
     * @param HttpClient $client
     *
     * @return RequestManager
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return RequestManager
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }
}
