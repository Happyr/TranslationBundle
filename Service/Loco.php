<?php

namespace Happyr\LocoBundle\Service;

use Happyr\LocoBundle\Exception\HttpException;
use Happyr\LocoBundle\Http\HttpAdapterInterface;


/**
 * @author Tobias Nyholm
 */
class Loco
{
    /**
     * @var HttpAdapterInterface httpAdapter
     */
    private $httpAdapter;

    /**
     * @var array projects
     */
    private $projects;

    /**
     * @var string targetDir
     */
    private $targetDir;

    /**
     * @var FilesystemUpdater filesystemService
     */
    private $filesystemService;

    /**
     * @param HttpAdapterInterface $httpAdapter
     * @param FilesystemUpdater           $fs
     * @param array                $projects
     * @param string                     $targetDir
     */
    public function __construct(HttpAdapterInterface $httpAdapter, FilesystemUpdater $fs, array $projects, $targetDir)
    {
        $this->httpAdapter = $httpAdapter;
        $this->projects = $projects;
        $this->targetDir = $targetDir;
        $this->filesystemService = $fs;
    }

    /**
     * @param array $messages
     *
     * @return int number of messages created
     */
    public function createMessages(array $messages)
    {
        $uploaded = array();
        foreach ($messages as $message) {
            if ($this->uploadMessageToLoco($message)) {
                $uploaded[] = $message;
            }
        }

        if (count($uploaded)>0) {
            $this->filesystemService->updateMessageCatalog($uploaded);
        }

        return count($uploaded);
    }



    /**
     * Create a new asset in Loco.
     *
     * @param array $message array(
     *                       count = 1,
     *                       domain = "navigation",
     *                       id = "logout",
     *                       locale = "sv",
     *                       state = 1,
     *                       translation = "logout"
     *                       )
     */
    protected function uploadMessageToLoco(array $message)
    {
        $project = $this->getProject($message);

        try {
            $response = $this->httpAdapter->send(
                'POST',
                'assets',
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => [
                        'id' => $message['id'],
                        'name' => $message['id'],
                        'type' => 'text',
                    ],
                ]
            );
        } catch (HttpException $e) {
            if ($e->getCode() === 409) {
                //conflict.. ignore
                return false;
            }
        }

        // if this project has multiple domains. Make sure to tag it
        if (!empty($project['domains'])) {
            $this->addTag($project, $response['id'], $message['domain']);
        }

        return true;
    }

    /**
     * @param array $message
     *
     * @return mixed
     */
    protected function getProject(array $message)
    {
        if (isset($this->projects[$message['domain']])) {
            return $this->projects[$message['domain']];
        }

        // Return the first project that has the correct domain and locale
        foreach ($this->projects as $project) {
            if (in_array($message['domain'], $project['domains'])) {
                if (in_array($message['locales'], $project['locale'])) {
                    return $project;
                }
            }
        }
    }

    /**
     * @param $project
     * @param $messageId
     * @param $domain
     */
    protected function addTag($project, $messageId, $domain)
    {
        $this->httpAdapter->send(
            'POST',
            sprintf('assets/%s/tags', $messageId),
            [
                'query' => ['key' => $project['api_key']],
                'body' => ['name' => $domain],
            ]
        );
    }

    /**
     * Download all the translations from Loco.
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
     * @param array  $data
     * @param array  $config
     * @param string $domain
     * @param bool   $useDomainAsFilter
     */
    protected function getUrls(array &$data, array &$config, $domain, $useDomainAsFilter)
    {
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
