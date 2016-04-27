<?php

namespace Icinga\Module\Elasticsearch;

use Exception;
use Icinga\Exception\IcingaException;
use Icinga\Module\Elasticsearch\RestApi\RestApiClient;

/**
 * @todo reimplement?
 */
class Event
{
    /** @var  RestApiClient */
    protected $client;

    protected $index;
    protected $type;
    protected $id;

    protected $found = false;
    protected $document;

    public function __construct(RestApiClient $client)
    {
        $this->client = $client;
    }

    public function fetch()
    {
        if (!$this->index or !$this->type or !$this->id)
            throw new Exception("index, type and id must be set for fetching!");

        $result = $this->client->fetchDocument($this->index, $this->type, $this->id);

        if ($result !== false) {
            $this->document = $result;
            return $this;
        }
        else return false;
    }

    /**
     * @return String
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param String $index
     * @throws Exception
     */
    public function setIndex($index)
    {
        if (defined($this->index))
            throw new Exception("Cannot change index!");
        $this->index = $index;
    }

    /**
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param String $type
     * @throws Exception
     */
    public function setType($type)
    {
        if (defined($this->type))
            throw new Exception("Cannot change type!");
        $this->type = $type;
    }

    /**
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param String $id
     * @throws Exception
     */
    public function setId($id)
    {
        if (defined($this->id))
            throw new Exception("Cannot change id!");
        $this->id = $id;
    }

    /**
     * @return  object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function update_partial(Array $data) {
        if ($this->document === null)
            throw new IcingaException("document must have been fetched before!");

        if (!$this->index or !$this->type or !$this->id)
            throw new IcingaException("index, type and id must be set for updating!");

        $result = $this->client->update(
            array($this->index, $this->type, $this->id),
            $data
        );

        if ($result !== false) {
            return true;
        }
        else return false;
    }
}
