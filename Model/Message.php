<?php
namespace Happyr\LocoBundle\Model;

/**
 * @author Tobias Nyholm
 */
class Message
{
    /**
     * @var int
     */
    private $count;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $state;

    /**
     * @var string
     */
    private $translation;

    /**
     * @param array $data
     * array( count = 1, domain = "navigation", id = "logout", locale = "sv", state = 1, translation = "logout" )
     */
    public function __construct(array $data)
    {
        $this->count = $data['count'];
        $this->domain = $data['domain'];
        $this->id = $data['id'];
        $this->locale = $data['locale'];
        $this->state = $data['state'];
        $this->translation = $data['translation'];
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     *
     * @return $this
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }


}
