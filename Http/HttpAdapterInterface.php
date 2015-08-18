<?php

namespace Happyr\LocoBundle\Http;

/**
 * @author Tobias Nyholm
 */
interface HttpAdapterInterface
{
    const BASE_URL = 'https://localise.biz/api/';
    public function downloadFiles(array $data);
    public function uploadFiles(array $data);

    /**
     * @param $method
     * @param $url
     * @param $data
     *
     */
    public function send($method, $url, $data);
}