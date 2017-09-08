<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use RuntimeException;
use Icinga\Data\Paginatable;
use Icinga\Data\Queryable;
use Icinga\Util\Json;
use iplx\Http\Client;
use iplx\Http\Request;

class Query implements Queryable, Paginatable
{
    const MAX_RESULT_WINDOW = 10000;

    protected $elastic;

    protected $fields;

    protected $filter;

    protected $index;

    protected $limit;

    protected $offset;

    protected $response;

    public function __construct(Elastic $elastic, array $fields = [])
    {
        $this->elastic = $elastic;

        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
     */
    public function from($target, array $fields = null)
    {
        $this->index = $target;

        if (! empty($fields)) {
            $this->fields = $fields;
        }

        return $this;
    }

    public function limit($count = null, $offset = null)
    {
        $this->limit = $count;
        $this->offset = $offset;

        return $this;
    }

    public function hasLimit()
    {
        return $this->limit !== null;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function hasOffset()
    {
        return $this->offset !== null;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function count()
    {
        $this->execute();

        $total = $this->response['hits']['total'];
        if ($total > self::MAX_RESULT_WINDOW) {
            return self::MAX_RESULT_WINDOW;
        }

        return $total;
    }

    public function filter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    protected function execute()
    {
        if ($this->response === null) {
            $client = new Client();

            $request = new Request(
                'GET',
                "{$this->elastic->getConfig()->uri}/{$this->index}/_search",
                ['Content-Type' => 'application/json'],
                json_encode(array_filter([
                    '_source'   => array_merge(['@timestamp'], $this->fields),
                    'query'     => $this->filter,
                    'from'      => $this->getOffset(),
                    'size'      => $this->getLimit(),
                    'sort'      => ['@timestamp' => 'desc']
                ]))
            );

            $response = Json::decode((string) $client->send($request)->getBody(), true);

            if (isset($response['error'])) {
                throw new RuntimeException(
                    'Got error from Elasticsearch: '. $response['error']['type'] . ': ' . $response['error']['reason']
                );
            }

            $this->response = $response;
        }
    }

    public function getFields()
    {
        $this->execute();

        $events = $this->response['hits']['hits'];

        $fields = [];

        if (! empty($events)) {
            $event = reset($events);

            Elastic::extractFields($event['_source'], $fields);
        }

        return $fields;
    }

    public function fetchAll()
    {
        $this->execute();

        return $this->response['hits']['hits'];
    }
}
