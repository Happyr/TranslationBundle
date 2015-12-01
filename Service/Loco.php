<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Exception\HttpException;
use Happyr\TranslationBundle\Http\HttpAdapterInterface;
use Happyr\TranslationBundle\Model\Message;
use Happyr\TranslationBundle\Translation\FilesystemUpdater;

/**
 * @author Tobias Nyholm
 */
class Loco implements TranslationServiceInterface
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
     * @var FilesystemUpdater filesystemService
     */
    private $filesystemService;

    /**
     * @param HttpAdapterInterface $httpAdapter
     * @param FilesystemUpdater    $fs
     * @param array                $projects
     */
    public function __construct(HttpAdapterInterface $httpAdapter, FilesystemUpdater $fs, array $projects)
    {
        $this->httpAdapter = $httpAdapter;
        $this->projects = $projects;
        $this->filesystemService = $fs;
    }

    /**
     * Make an API call. Use this function if you want more freedom an call the Loco API with whatever you want.
     *
     * @param string $projectName The name of the project that you have configured it in Symfony
     * @param string $method      The HTTP method
     * @param string $url         The url after "https://localise.biz/api/"
     * @param array  $query       any query parameters. The "key" parameter will be added automatically
     * @param mixed  $data        This is the body of the request
     * @param array  $options     Other Guzzle options
     *
     * @return array
     */
    public function api($projectName, $method, $url, array $query = array(), $data = null, array $options = array())
    {
        if (!isset($this->projects[$projectName])) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find project with name %s. Valid names are: %s',
                $projectName,
                implode(', ', array_keys($this->projects))
            ));
        }
        $project = $this->projects[$projectName];

        $options['query'] = array_merge(['key' => $project['api_key']], $query);
        if ($data) {
            $options['body'] = $data;
        }

        return $this->httpAdapter->send(
            $method,
            $url,
            $options
        );
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
        $flags = ['fuzzy', 'error', 'review', 'pending'];

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
    public function createAsset(Message $message)
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
                $notes = '';
                foreach ($message->getParameters() as $key => $value) {
                    $notes .= 'Parameter: '.$key.' (i.e. : '.$value.")\n";
                }

                $this->httpAdapter->send(
                    'PATCH',
                    sprintf('assets/%s.json', $message->getId()),
                    [
                        'query' => ['key' => $project['api_key']],
                        'json' => ['notes' => $notes],
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
        if (!is_dir($this->filesystemService->getTargetDir())) {
            mkdir($this->filesystemService->getTargetDir(), 0777, true);
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
        $query = $this->getExportQueryParams($config['api_key']);

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

            $this->flatten($response);

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
     * Flattens an nested array of translations.
     *
     * The scheme used is:
     *   'key' => array('key2' => array('key3' => 'value'))
     * Becomes:
     *   'key.key2.key3' => 'value'
     *
     * This function takes an array by reference and will modify it
     *
     * @param array  &$messages The array that will be flattened
     * @param array  $subnode   Current subnode being parsed, used internally for recursive calls
     * @param string $path      Current path being parsed, used internally for recursive calls
     */
    private function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path.'.'.$key : $key;
                $this->flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path.'.'.$key] = $value;
            }
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
        $query = $this->getExportQueryParams($config['api_key']);

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            // Build url
            $url = sprintf('export/locale/%s.%s?%s', $locale, $this->filesystemService->getFileExtension(), http_build_query($query));
            $path = sprintf('%s/%s.%s.%s', $this->filesystemService->getTargetDir(), $domain, $locale, $this->filesystemService->getFileExtension());

            $data[$url] = $path;
        }
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function getExportQueryParams($apiKey)
    {
        $data = array(
            'key' => $apiKey,
            'index' => 'id',
            'status' => 'translated',
        );
        switch ($this->filesystemService->getFileExtension()) {
            case 'php':
                $data['format'] = 'zend'; // 'Zend' will give us a flat array
            case 'xlf':
            default:
                $data['format'] = 'symfony';
        }

        return $data;
    }
}
