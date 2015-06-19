<?php

namespace Icinga\Module\Logstash;

use Icinga\Module\Logstash\Curl;
use Exception;
use stdClass;

class Event extends ElasticsearchBackend
{
    protected $index;
    protected $type;
    protected $id;

    protected $found = false;
    protected $version;
    protected $source;

    public function fetch()
    {
        if (!$this->getElasticsearch()) {
            throw new Exception("Elasticsearch URL has not be configured!");
        }

        if (!$this->index or !$this->type or !$this->id)
            throw new Exception("index, type and id must be set for fetching!");

        $result = $this->curl->get_json(
            sprintf('/%s/%s/%s',
                $this->index,
                $this->type,
                $this->id
            )
        );

        if ($result !== false or $result->found !== false) {
            $this->found = true;
            $this->version = $result->_version;
            $this->source = $result->_source;
        }
        return $this;
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
     * @return Integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return stdClass
     */
    public function getSource()
    {
        return $this->source;
    }

    public function found() {
        return (bool) $this->found;
    }

}
