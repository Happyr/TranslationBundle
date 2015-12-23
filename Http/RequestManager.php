<?php

namespace Happyr\TranslationBundle\Http;

use Happyr\TranslationBundle\Exception\HttpException;
use Happyr\TranslationBundle\Translation\FilesystemUpdater;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

class RequestManager
{
    /**
     * @var HttpClient
     */
    private $client;

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
    public function send($method, $url, $body = null, $headers=array())
    {
        $request = MessageFactoryDiscovery::find()->createRequest($method, $url, $headers, $body);

        $response = $this->getClient()->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            // TODO throw better exception
            throw new HttpException();
        }

        // TODO add more error checks
        return json_decode($response->getBody()->__toString(), true);
    }

    /**
     * @return HttpClient
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = HttpClientDiscovery::find();
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
}