<?php

namespace Happyr\TranslationBundle\Http;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Happyr\TranslationBundle\Exception\HttpException;

/**
 * @author Tobias Nyholm
 */
class Guzzle6Adapter implements HttpAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function send($method, $url, $data)
    {
        $client = new Client([
            'base_uri' => HttpAdapterInterface::BASE_URL,
        ]);

        try {
            $response = $client->request($method, $url, $data);
        } catch (ClientException $e) {
            throw new HttpException('Could not transfer data to Loco', $e->getCode(), $e);
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @param array $data array($url=>$savePath)
     */
    public function downloadFiles(array $data)
    {
        trigger_error('Not yet implemented', E_USER_WARNING);

        return;

        $client = new Client();

        $requests = array();
        foreach ($data as $url => $path) {
            $requests[] = new Request('GET', HttpAdapterInterface::BASE_URL.$url, [
                'save_to' => $path,
            ]);
        }

        $pool = new Pool($client, $requests, [
            'concurrency' => 5,
            'fulfilled' => function ($response, $index) {
                // this is delivered each successful response
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request
                //TODO error handling
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }
}
