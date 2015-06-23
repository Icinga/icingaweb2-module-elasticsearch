<?php

namespace Icinga\Module\Logstash;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Limitable;
use Icinga\Data\Sortable;
use Icinga\Data\QueryInterface;
use Icinga\Exception\ProgrammingError;
use Exception;

use Icinga\Module\Logstash\Curl;

class Search extends ElasticsearchBackend implements QueryInterface
{
    use IcingaStatus;

    protected $query;
    protected $filter = array();
    protected $filter_query;
    protected $fields = array();

    protected $without_ack = false;

    protected $sort_field;
    protected $sort_direction;
    protected $size;
    protected $from;

    protected $took;
    protected $hits;
    protected $total;
    protected $timed_out;

    public function __construct($elasticsearch=null) {
        parent::__construct($elasticsearch);
        $this->order('@timestamp', 'desc');
    }

    protected function search()
    {
        if (!$this->getElasticsearch()) {
            throw new Exception("Elasticsearch URL has not be configured!");
        }

        $post = array(
            'query' => $this->query
        );
        if ($this->filter)
            $post['filter']['and'] = $this->filter;

        if ($this->filter_query)
            $post['filter']['and'][] = $this->filter_query;

        if ($this->without_ack === true)
            $post['filter']['and'][] = $this->buildFilterQueryString('NOT icinga_acknowledge:1');

        if ($this->sort_field) {
            $post['sort'] = array(
                array(
                    $this->sort_field => array(
                        'order' => $this->sort_direction ? $this->sort_direction : 'desc'
                    )
                ),
                /* TODO: find way for stable sorting
                array(
                    '_id' => array(
                        'order' => 'asc'
                    )
                )
                */
            );
        }

        /*
        if (count($this->fields) > 0) {
            $fields = $this->fields;
            if (count($this->icinga_status_fields) > 0) {
                $fields = array_merge($fields, $this->icinga_status_fields);
            }
            $post['fields'] = array_unique($fields);
        }
        */

        if ($this->size)
            $post['size'] = $this->size;

        if ($this->from)
            $post['from'] = $this->from;

        $result = $this->curl->post_json(
            '/_search',
            $post
        );

        if ($result->timed_out === true)
            throw new Exception("Elasticsearch query timed out after %sms", $result->took);

        $this->took = $result->took;
        $this->total = $result->hits->total;
        $this->hits = $result->hits->hits;

        return true;
    }

    public function setQueryString($query_string, $default_operator='and')
    {
        $this->query = array(
            'query_string' => array(
                'default_operator' => $default_operator,
                'query' => $query_string,
            )
        );
    }

    public function getQueryString()
    {
        if ($this->query && array_key_exists($this->query, "query_string")) {
            return $this->query["query_string"]["query"];
        }
        else return false;
    }

    protected function buildFilterQueryString($query_string, $default_operator='and') {
        return array(
            'query' => array(
                'query_string' => array(
                    'default_operator' => $default_operator,
                    'query' => $query_string,
                )
            )
        );
    }

    public function setFilterQueryString($query_string, $default_operator='and')
    {
        $this->filter_query = $this->buildFilterQueryString($query_string, $default_operator);
    }

    public function getFilterQueryString()
    {
        if ($this->filter_query) {
            return $this->filter_query["query"]["query_string"]["query"];
        }
        else return false;
    }

    public function clearFilter()
    {
        $this->filter = array();
    }

    /* TODO old
    public function addFilter($object) {
        if (!$this->filter) $this->filter = array();
        $this->filter[] = $object;
    }
    */

    public function addFilterTimeRange($from, $to, $field='@timestamp') {
        $this->addFilter(array(
            'range' => array(
                $field => array(
                    'from' => $from,
                    'to'   => $to
                )
            )
        ));
    }

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @return  array
     */
    public function fetchAll()
    {
        $this->search();
        $hits = array();
        foreach ($this->hits as $hit) {
            $h = array(
                '_index' => $hit->_index,
                '_type' => $hit->_type,
                '_id' => $hit->_id,
            );
            if (property_exists($hit, 'fields'))
                foreach ($hit->fields as $key => $value) {
                    $h[$key] = $value[0];
                }
            elseif (property_exists($hit, '_source'))
                foreach ($hit->_source as $key => $value) {
                    $h[$key] = $value;
                }

            $this->evalIcingaStatus($h);

            $hits[] = $h;
        }
        return $hits;
    }

    /**
     * Fetch the first row of the result set
     *
     * @return  mixed
     */
    public function fetchRow()
    {
        // TODO: Implement fetchRow() method.
    }

    /**
     * Fetch the first column of all rows of the result set as an array
     *
     * @return  array
     */
    public function fetchColumn()
    {
        // TODO: Implement fetchColumn() method.
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @return  string
     */
    public function fetchOne()
    {
        // TODO: Implement fetchOne() method.
    }

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @return  array
     */
    public function fetchPairs()
    {
        // TODO: Implement fetchPairs() method.
    }

    public function applyFilter(Filter $filter)
    {
        // TODO: Implement applyFilter() method.
    }

    public function setFilter(Filter $filter)
    {
        // TODO: Implement setFilter() method.
    }

    public function getFilter()
    {
        // TODO: Implement getFilter() method.
    }

    public function where($condition, $value = null)
    {
        // TODO: Implement where() method.
    }

    /**
     * Set a limit count and offset
     *
     * @param   int $count Number of rows to return
     * @param   int $offset Start returning after this many rows
     *
     * @return  self
     */
    public function limit($count = null, $offset = null)
    {
        $this->size = $count;
        $this->from = $offset;
    }

    /**
     * Whether a limit is set
     *
     * @return bool
     */
    public function hasLimit()
    {
        return $this->size !== null;
    }

    /**
     * Get the limit if any
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->size;
    }

    /**
     * Whether an offset is set
     *
     * @return bool
     */
    public function hasOffset()
    {
        return $this->from !== null;
    }

    /**
     * Get the offset if any
     *
     * @return int|null
     */
    public function getOffset()
    {
        return $this->from;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return $this->total;
    }

    /**
     * Sort result set by the given field (and direction)
     *
     * Preferred usage:
     * <code>
     * $query->order('field, 'ASC')
     * </code>
     *
     * @param  string $field
     * @param  string $direction
     *
     * @return self
     */
    public function order($field, $direction = null)
    {
        assert($direction == 'asc' || $direction == 'desc', '$direction must be asc or desc');
        $this->sort_field = $field;
        $this->sort_direction = $direction;
    }

    /**
     * Whether an order is set
     *
     * @return bool
     */
    public function hasOrder()
    {
        return $this->sort_field !== null;
    }

    /**
     * Get the order if any
     *
     * @return array|null
     */
    public function getOrder()
    {
        return [ $this->sort_field, $this->sort_direction ];
    }

    public function addFilter(Filter $filter)
    {
        // TODO: Implement addFilter() method.
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return Integer milliseconds
     */
    public function getTook()
    {
        return $this->took;
    }

    /**
     * @return boolean
     */
    public function isWithoutAck()
    {
        return $this->without_ack;
    }

    /**
     * @param boolean $without_ack
     */
    public function setWithoutAck($without_ack)
    {
        $this->without_ack = $without_ack;
    }
}
