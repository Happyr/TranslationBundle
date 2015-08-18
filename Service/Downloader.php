<?php

namespace Happyr\LocoBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Pool;
use Happyr\LocoBundle\Http\HttpAdapterInterface;

/**
 * @author Cliff Odijk (cmodijk)
 *
 * @author Tobias Nyholm
 */
class Downloader
{
    /**
     * @var HttpAdapterInterface httpAdapter
     */
    private $httpAdapter;

    private $targetDir;

    private $projects;

    /**
     * Downloader constructor.
     *
     * @param HttpAdapterInterface $httpAdapter
     * @param array                     $projects
     * @param string                     $targetDir
     */
    public function __construct(HttpAdapterInterface $httpAdapter, $projects, $targetDir)
    {
        $this->httpAdapter = $httpAdapter;
        $this->targetDir = $targetDir;
        $this->projects = $projects;
    }

    /**
     * Download all the translations from Loco
     */
    public function download()
    {
        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0777, true);
        }

        $data = [];
        foreach ($this->projects as $name => $config) {
            if (empty($config['domains'])) {
                $this->getUrls($data, $config, $name, false);
            } else {
                foreach ($config['domain'] as $domain) {
                    $this->getUrls($data, $config, $domain, true);
                }
            }
        }
        $this->httpAdapter->downloadFiles($data);
    }

    /**
     * @param array $data
     * @param array $config
     * @param string      $domain
     * @param boolean      $useDomainAsFilter
     */
    public function getUrls(array &$data, array &$config, $domain, $useDomainAsFilter) {
        $query = array(
            'key' => $config['api_key'],
            'format' => 'symfony',
            'index' => 'id',
        );

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            // Build url
            $url = sprintf('export/locale/%s.%s?%s', $locale, 'phps', http_build_query($query));
            $path = sprintf('%s/%s.%s.%s', $this->targetDir, $domain, $locale, 'phps');

            $data[$url] = $path;
        }
    }
}
