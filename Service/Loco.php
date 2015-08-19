<?php

namespace Happyr\LocoBundle\Service;

use Happyr\LocoBundle\Exception\HttpException;
use Happyr\LocoBundle\Http\HttpAdapterInterface;
use Happyr\LocoBundle\Model\Message;

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
     * @param FilesystemUpdater    $fs
     * @param array                $projects
     * @param string               $targetDir
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
    public function createAssets(array $messages)
    {
        $uploaded = array();
        foreach ($messages as $message) {
            if ($this->createAsset($message)) {
                $uploaded[] = $message;
            }
        }

        if (count($uploaded) > 0) {
            $this->filesystemService->updateMessageCatalog($uploaded);
        }

        return count($uploaded);
    }

    /**
     * Fetch a translation form Loco.
     *
     * @param Message $message
     */
    public function fetchTranslation(Message $message, $updateFs = false)
    {
        $project = $this->getProject($message);

        try {
            $response = $this->httpAdapter->send(
                'GET',
                sprintf('translations/%s/%s', $message->getId(), $message->getLocale()),
                ['query' => ['key' => $project['api_key']]]
            );
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                //Message does not exist
                return;
            }
        }

        $logoTranslation = $response['translation'];
        $messageTranslation = $message->getTranslation();
        $message->setTranslation($logoTranslation);

        // update filesystem
        if ($updateFs && $logoTranslation !== $messageTranslation) {
            $this->filesystemService->updateMessageCatalog([$message]);
        }

        return $logoTranslation;
    }

    /**
     * Update the translation in Loco.
     *
     * @param Message $message
     */
    public function updateTranslation(Message $message)
    {
        $project = $this->getProject($message);

        try {
            $this->httpAdapter->send(
                'POST',
                sprintf('translations/%s/%s', $message->getId(), $message->getLocale()),
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => $message->getTranslation(),
                ]
            );
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                //Message does not exist
                return false;
            }
        }

        $this->filesystemService->updateMessageCatalog([$message]);

        return true;
    }

    /**
     * If there is something wrong with the translation, please flag it.
     *
     * @param Message $message
     * @param int     $type    0: Fuzzy, 1: Error, 2: Review, 3: Pending
     *
     * @return bool
     */
    public function flagTranslation(Message $message, $type = 0)
    {
        $project = $this->getProject($message);
        $flags = ['fuzzy', 'error', 'review','pending'];

        try {
            $this->httpAdapter->send(
                'POST',
                sprintf('translations/%s/%s/flag', $message->getId(), $message->getLocale()),
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => ['flag' => $flags[$type]],
                ]
            );
        } catch (HttpException $e) {
            if ($e->getCode() === 404) {
                //Message does not exist
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new asset in Loco.
     *
     * @param Message $message
     *
     * @return bool
     */
    protected function createAsset(Message $message)
    {
        $project = $this->getProject($message);

        try {
            $response = $this->httpAdapter->send(
                'POST',
                'assets',
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => [
                        'id' => $message->getId(),
                        'name' => $message->getId(),
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
            $this->addTagToAsset($project, $response['id'], $message->getDomain());
        }

        return true;
    }

    /**
     * @param Message $message
     *
     * @return array
     */
    protected function getProject(Message $message)
    {
        if (isset($this->projects[$message->getDomain()])) {
            return $this->projects[$message->getDomain()];
        }

        // Return the first project that has the correct domain and locale
        foreach ($this->projects as $project) {
            if (in_array($message->getDomain(), $project['domains'])) {
                if (in_array($project['locales'], $message->getLocale())) {
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
    protected function addTagToAsset($project, $messageId, $domain)
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
    public function downloadAllTranslations()
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
