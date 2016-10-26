<?php

namespace Happyr\TranslationBundle\Service;

use Happyr\TranslationBundle\Exception\HappyrTranslationException;
use Happyr\TranslationBundle\Exception\HttpException;
use Happyr\TranslationBundle\Http\RequestManager;
use Happyr\TranslationBundle\Model\Message;
use Happyr\TranslationBundle\Translation\FilesystemUpdater;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Loco implements TranslationServiceInterface
{
    const BASE_URL = 'https://localise.biz/api/';

    /**
     * @var RequestManager
     */
    private $requestManager;

    /**
     * @var array projects
     */
    private $projects;

    /**
     * @var FilesystemUpdater filesystemService
     */
    private $filesystemService;

    /**
     * @var TranslatorInterface filesystemService
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     * @param RequestManager $requestManager
     * @param FilesystemUpdater $fs
     * @param array $projects
     */
    public function __construct(RequestManager $requestManager, FilesystemUpdater $fs, TranslatorInterface $translator, array $projects)
    {
        $this->translator = $translator;
        $this->requestManager = $requestManager;
        $this->projects = $projects;
        $this->filesystemService = $fs;
    }

    /**
     * @param $key
     * @param $method
     * @param $resource
     * @param null $body
     * @param string $type
     * @param array $extraQuery
     * @return array
     * @throws HttpException
     */
    protected function makeApiRequest($key, $method, $resource, $body = null, $type = 'form', $extraQuery = array())
    {
        $headers = array();
        if ($body !== null) {
            if ($type === 'form') {
                if (is_array($body)) {
                    $body = http_build_query($body);
                }
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            } elseif ($type === 'json') {
                $body = json_encode($body);
                $headers['Content-Type'] = 'application/json';
            }
        }

        $query = array_merge($extraQuery, ['key' => $key]);
        $url = self::BASE_URL . $resource . '?' . http_build_query($query);

        return $this->requestManager->send($method, $url, $body, $headers);
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
            $resource = sprintf('translations/%s/%s', $message->getId(), $message->getLocale());
            $response = $this->makeApiRequest($project['api_key'], 'GET', $resource);
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
            $resource = sprintf('translations/%s/%s', $message->getId(), $message->getLocale());
            $this->makeApiRequest($project['api_key'], 'POST', $resource, $message->getTranslation());
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
     * @param int $type 0: Fuzzy, 1: Incorrect, 2: Provisional, 3: Unapproved, 4: Incomplete
     *
     * @return bool
     */
    public function flagTranslation(Message $message, $type = 0)
    {
        $project = $this->getProject($message);
        $flags = ['fuzzy', 'incorrect', 'provisional', 'unapproved', 'incomplete'];

        try {
            $resource = sprintf('translations/%s/%s/flag', $message->getId(), $message->getLocale());
            $this->makeApiRequest($project['api_key'], 'POST', $resource, ['flag' => $flags[$type]]);
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
            $response = $this->makeApiRequest($project['api_key'], 'POST', 'assets', [
                'id' => $message->getId(),
                'name' => $message->getId(),
                'type' => 'text',
                // Tell Loco not to translate the asset
                'default' => 'untranslated',
            ]);

            if ($message->hasParameters()) {
                // Send those parameter as a note to Loco
                $notes = '';
                foreach ($message->getParameters() as $key => $value) {
                    if (!is_array($value)) {
                        $notes .= 'Parameter: ' . $key . ' (i.e. : ' . $value . ")\n";
                    } else {
                        foreach ($value as $k => $v) {
                            $notes .= 'Parameter: ' . $k . ' (i.e. : ' . $v . ")\n";
                        }
                    }
                }

                $resource = sprintf('assets/%s.json', $message->getId());
                $this->makeApiRequest($project['api_key'], 'PATCH', $resource, ['notes' => $notes], 'json');
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
                if (in_array($message->getLocale(), $project['locales'])) {
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
        $resource = sprintf('assets/%s/tags', $messageId);
        $this->makeApiRequest($project['api_key'], 'POST', $resource, ['name' => $domain]);
    }

    /**
     * Download all the translations from Loco. This will replace all the local files.
     * This is a quick method of getting all the latest translations and assets.
     */
    public function downloadAllTranslations()
    {
        $data = [];
        foreach ($this->projects as $name => $config) {
            if (empty($config['domains'])) {
                $this->getUrls($data, $config, $name, false);
            } else {
                foreach ($config['domains'] as $domain) {
                    $this->getUrls($data, $config, $domain, true);
                }
            }
        }
        $this->requestManager->downloadFiles($this->filesystemService, $data);
    }

    /**
     * Upload all the translations from the symfony project into Loco. This will override
     * every changed strings in loco
     */
    public function uploadAllTranslations()
    {
        foreach ($this->projects as $name => $config) {
            if (empty($config['domains'])) {
                $this->doUploadDomains($config, $name, false);
            } else {
                foreach ($config['domains'] as $domain) {
                    $this->doUploadDomains($config, $domain, true);
                }
            }
        }
    }

    /**
     * @param array $config
     * @param       $domain
     * @param       $useDomainAsFilter
     */
    protected function doUploadDomains(array &$config, $domain, $useDomainAsFilter)
    {
        $query = $this->getExportQueryParams($config['api_key']);

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            $extension = $this->filesystemService->getFileExtension();
            $file = $this->filesystemService->getTargetDir();
            $file .= sprintf('/%s.%s.%s', $domain, $locale, $extension);

            if (is_file($file)) {
                $query = [
                    'index' => 'id',
                    'tag' => $domain,
                    'locale'=> $locale
                ];

                $resource = sprintf('import/%s', $extension);
                $response = $this->makeApiRequest($config['api_key'], 'POST', $resource, file_get_contents($file), 'form', $query);
                $this->flatten($response);
            } else {
                throw new FileNotFoundException(sprintf("Can't find %s file, perhaps you should generate the translations file ?", $file));
            }
        }
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
                foreach ($config['domains'] as $domain) {
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
            $resource = sprintf('export/locale/%s.%s', $locale, 'json');
            $response = $this->makeApiRequest($config['api_key'], 'GET', $resource, ['query' => $query]);

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
     * @param array &$messages The array that will be flattened
     * @param array $subnode Current subnode being parsed, used internally for recursive calls
     * @param string $path Current path being parsed, used internally for recursive calls
     */
    private function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path . '.' . $key : $key;
                $this->flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path . '.' . $key] = $value;
            }
        }
    }

    /**
     * @param array $data
     * @param array $config
     * @param string $domain
     * @param bool $useDomainAsFilter
     */
    protected function getUrls(array &$data, array $config, $domain, $useDomainAsFilter)
    {
        $query = $this->getExportQueryParams($config['api_key']);

        if ($useDomainAsFilter) {
            $query['filter'] = $domain;
        }

        foreach ($config['locales'] as $locale) {
            // Build url
            $url = sprintf('%sexport/locale/%s.%s?%s', self::BASE_URL, $locale, $this->filesystemService->getFileExtension(), http_build_query($query));
            $fileName = sprintf('%s.%s.%s', $domain, $locale, $this->filesystemService->getFileExtension());

            $data[$url] = $fileName;

            if (count($config['tags']) !== 0) {
                $query = $this->getExportQueryParams($config['api_key'], $config['tags']);

                // Build url
                $url = sprintf('%sexport/locale/%s.%s?%s', self::BASE_URL, $locale, $this->filesystemService->getFileExtension(), http_build_query($query));
                $fileName = sprintf('%s.%s.%s', $domain.'-'.(implode($config['tags'], '-')), $locale, $this->filesystemService->getFileExtension());

                $data[$url] = $fileName;
            }
        }
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function getExportQueryParams($key, $tags=null)
    {
        $data = array(
            'index' => 'id',
            'status' => 'translated',
            'key' => $key,
        );

        if ($tags !== null) {
            $data['filter'] = implode($tags, ',');
        }

        switch ($this->filesystemService->getFileExtension()) {
            case 'php':
                $data['format'] = 'zend'; // 'Zend' will give us a flat array
                break;
            case 'xlf':
            default:
                $data['format'] = 'symfony';
        }

        return $data;
    }
}
