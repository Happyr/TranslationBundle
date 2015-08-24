<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Exception\HttpException;
use Happyr\TranslationBundle\Http\HttpAdapterInterface;
use Happyr\TranslationBundle\Model\Message;

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
            throw $e;
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
                //Asset does not exist
                if ($this->createAsset($message)) {
                    //Try again
                    return $this->updateTranslation($message);
                }

                return false;
            }
            throw $e;
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
            throw $e;
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
                        // Tell Loco not to translate the asset
                        'default' => 'untranslated',
                    ],
                ]
            );

            if ($message->hasParameters()) {
                // Send those parameter as a note to Loco
                $notes='';
                foreach ($message->getParameters() as $key => $value) {
                    $notes .= 'Parameter: '.$key.' (i.e. : '.$value.")\n";
                }

                $this->httpAdapter->send(
                    'PATCH',
                    sprintf('assets/%s.json', $message->getId()),
                    [
                        'query' => ['key' => $project['api_key']],
                        'json' => [ 'notes'=> $notes ],
                    ]
                );
            }
        } catch (HttpException $e) {
            if ($e->getCode() === 409) {
                //conflict.. ignore
                return false;
            }
            throw $e;
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
     * Download all the translations from Loco. This will replace all the local files.
     * This is a quick method of getting all the latest translations and assets.
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
     * Synchronize all the translations with Loco. This will keep placeholders. This function is slower
     * than just to download the translations.
     */
    public function synchronizeAllTranslations()
    {
        foreach ($this->projects as $name => $config) {
            if (empty($config['domains'])) {
                $this->doSynchronizeDomain($config, $name, false);
            } else {
                foreach ($config['domain'] as $domain) {
                    $this->doSynchronizeDomain($config, $domain, true);
                }
            }
        }
    }

    /**
     * @param array $config
     * @param       $domain
     * @param       $useDomainAsFilter
     */
    protected function doSynchronizeDomain(array &$config, $domain, $useDomainAsFilter)
    {
        $query = array(
            'key' => $config['api_key'],
            // 'Zend' will give us a flat array
            'format' => 'zend',
            'index' => 'id',
        );

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            try {
                $response = $this->httpAdapter->send(
                    'GET',
                    sprintf('export/locale/%s.%s', $locale, 'json'),
                    [
                        'query' => $query,
                    ]
                );
            } catch (HttpException $e) {
                //TODO error handling
                throw $e;
            }

            $messages = array();
            foreach ($response as $id => $translation) {
                $messages[] = new Message([
                    'count' => 1,
                    'domain' => $domain,
                    'id' => $id,
                    'locale' => $locale,
                    'state' => 1,
                    'translation' => $translation,
                ]);
            }

            $this->filesystemService->updateMessageCatalog($messages);
        }
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
            // 'Zend' will give us a flat array
            'format' => 'zend',
            'index' => 'id',
        );

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            // Build url
            $url = sprintf('export/locale/%s.%s?%s', $locale, FilesystemUpdater::FILE_EXTENSION, http_build_query($query));
            $path = sprintf('%s/%s.%s.%s', $this->targetDir, $domain, $locale, FilesystemUpdater::FILE_EXTENSION);

            $data[$url] = $path;
        }
    }
}
