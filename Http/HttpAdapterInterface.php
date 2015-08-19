<?php

namespace Happyr\LocoBundle\Http;

use Happyr\LocoBundle\Exception\HttpException;

/**
 * @author Tobias Nyholm
 */
interface HttpAdapterInterface
{
    const BASE_URL = 'https://localise.biz/api/';

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function downloadFiles(array $data);

    /**
     * @param string $method
     * @param string $url
     * @param array  $data
     *
     * @return array
     *
     * @throws HttpException
     */
    public function send($method, $url, $data);
}
