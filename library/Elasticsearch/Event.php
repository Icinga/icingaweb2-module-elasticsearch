<?php

namespace Icinga\Module\Elasticsearch;

use Exception;
use Icinga\Repository\RepositoryQuery;

/**
 * Retrieving and handling single events
 */
class Event
{
    /**
     * The query to access events
     * @var RepositoryQuery
     */
    protected $query;

    /**
     * Elasticsearch index
     * @var string
     */
    protected $index;

    /**
     * Elasticsearch document type
     * @var string
     */
    protected $type;

    /**
     * Elasticsearch document ID
     * @var string
     */
    protected $id;

    /**
     * UNIX timestamp
     * @var int
     */
    protected $timestamp;

    /**
     * Elasticsearch document content
     * @var \stdClass
     */
    protected $document;

    /**
     * Load an Event from Elasticsearch using a RepositoryQuery
     *
     * Please specify all restrictions in the query, this will only
     * filter by ID in addition.
     *
     * @param  RepositoryQuery $query  Query for the ElasticsearchRepository
     * @param  string          $id     Elasticsearch document ID
     *
     * @return null|static
     */
    public static function fromRepository(RepositoryQuery $query, $id)
    {
        $self = new static;

        $self->query = clone $query;
        $self->query->where('_id', $id);

        $document = $self->query->fetchRow();
        if ($document === false) {
            return null;
        }

        $self->document = $document;

        $self->index = $document->_index;
        $self->type = $document->_type;
        $self->id = $document->_id;
        $self->timestamp = strtotime($document->{'@timestamp'});

        return $self;
    }

    /**
     * Get the Elasticsearch index of the event
     *
     * @return String
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Get the Elasticsearch type of the event
     *
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the Elasticsearch ID of the event
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get a single value from the Elasticsearch document
     *
     * @param  string  $key  Key in the Elasticsearch document
     *
     * @return null
     */
    public function get($key)
    {
        if (property_exists($this->document, $key)) {
            return $this->document->{$key};
        }
        else {
            return null;
        }
    }

    /**
     * Get all data from the Elasticsearch document as an associative Array
     *
     * @return array
     */
    public function getAll()
    {
        $data = array();
        foreach ($this->document as $var => $value) {
            $p = substr($var, 0, 1);
            if ($var === 'type' || $p === '_' || $p === '@') {
                continue;
            }

            $data[$var] = $value;
        }
        return $data;
    }

    /**
     * Get the UNIX timestamp for the Event
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Return object to export as JSON
     *
     * @return \stdClass
     */
    public function getJSONObject()
    {
        return $this->document;
    }

    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    /* TODO: re-implement!
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
    */
}
