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
     * @param HttpAdapterInterface $httpAdapter
     * @param array                $projects
     */
    public function __construct(HttpAdapterInterface $httpAdapter, array $projects)
    {
        $this->httpAdapter = $httpAdapter;
        $this->projects = $projects;
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
    public function createNewMessage(array $message)
    {
        $project = $this->getProject($message);

        try {
            $response = $this->httpAdapter->send(
                'POST',
                'assets',
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => [
                        'id' => md5($message['id']),
                        'name' => $message['id'],
                        'type' => 'text',
                    ],
                ]
            );
        } catch (HttpException $e) {
            if ($e->getCode() === 409) {
                //conflict.. ignore
                return;
            }
        }

        // if this project has multiple domains. Make sure to tag it
        if (!empty($project['domains'])) {
            $this->httpAdapter->send(
                'POST',
                sprintf('assets/%s/tags', $response['id']),
                [
                    'query' => ['key' => $project['api_key']],
                    'body' => ['name' => $message['domain']],
                ]
            );
        }
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
}
