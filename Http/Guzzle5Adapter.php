<?php

namespace Happyr\LocoBundle\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;

/**
 * @author Tobias Nyholm
 */
class Guzzle5Adapter implements HttpAdapterInterface
{
    /**
     * @param $method
     * @param $url
     * @param $data
     */
    public function send($method, $url, $data)
    {
        // TODO: Implement send() method.
    }

    /**
     * @param array $data array($url=>$savePath)
     */
    public function downloadFiles(array $data)
    {
        $client = new Client();

        $requests = array();
        foreach ($data as $url => $path) {
            $requests[] = $client->createRequest('GET', HttpAdapterInterface::BASE_URL.$url, [
                'save_to'=>$path,
            ]);
        }

        // Results is a GuzzleHttp\BatchResults object.
        $results = Pool::batch($client, $requests);

        // Retrieve all failures.
        foreach ($results->getFailures() as $requestException) {
            //TODO error handling
            throw new \Exception($requestException->getMessage());
        }
    }

    public function uploadFiles(array $data)
    {

    }
}