<?php

namespace Happyr\LocoBundle\Http;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * @author Tobias Nyholm
 */
class Guzzle6Adapter implements HttpAdapterInterface
{
    /**
     * @param $method
     * @param $url
     * @param $data
     */
    public function send($method, $url, $data)
    {
        $client = new Client(['base_url' => HttpAdapterInterface::BASE_URL]);
        $response = $client->send($client->createRequest($method, $url, $data));

        return (string) $response->getBody();
    }

    /**
     * @param array $data array($url=>$savePath)
     */
    public function downloadFiles(array $data)
    {
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

    public function uploadFiles(array $data)
    {
        user_error('Not implemented', E_USER_ERROR);
    }
}
