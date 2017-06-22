<?php

namespace Icinga\Module\Elasticsearch;

use Icinga\Application\Config;
use Icinga\Module\Elasticsearch\Repository\ElasticsearchRepository;
use Icinga\Module\Elasticsearch\RestApi\RestApiQuery;
use Icinga\Repository\RepositoryQuery;

class EventBackend extends ElasticsearchRepository
{
    /**
     * {@inheritdoc}
     */
    public $searchColumns = array('message');

    /**
     * @param   $type
     * @return  $this
     */
    public function setBaseTable($type)
    {
        $this->baseTable = $type;
        return $this;
    }

    /**
     * Initialize the query columns from the Elasticsearch mapping data.
     *
     * @todo  We should probaly cache the values, maybe even in fetchColumns?
     */
    public function initializeQueryColumns()
    {
        $types = array();
        $table = '_all';
        if ($this->baseTable !== null) {
            $types[] = $table = $this->baseTable;
        }
        $columns = array(
            $table => $this->ds->fetchColumns(array($this->index), $types),
        );
        return $columns;
    }

    public function initializeFilterColumns()
    {
        $columns = array();
        foreach ($this->queryColumns[$this->baseTable ?: '_all'] as $column) {
            if (strpos($column, '_') !== 0) {
                $columns[] = $column;
            }
        }
        return $columns;
    }

    /**
     * Sets index for search.
     *
     * This will set the table to null, if the default is an internal '_all'.
     *
     * @param   string                $documentType  The Elasticsearch type
     * @param   RepositoryQuery|null  $query         The user query
     *
     * @return  null
     */
    public function requireTable($documentType, RepositoryQuery $query = null)
    {
        if ($query !== null) {
            /** @var RestApiQuery $real_query */
            $real_query = $query->getQuery();
            $real_query->setIndices(array($this->getIndex()));
            if ($documentType !== null) {
                $real_query->setTypes(array($documentType));
            }
        }
        if ($documentType === null or $documentType === '_all') {
            return null;
        }
        return parent::requireTable($documentType, $query);
    }

    /**
     * {@inheritdoc}
     */
    static public function fromConfig()
    {
        $backend = parent::fromConfig();

        // TODO: move this to log types #11636
        $resourceConfig = Config::module('elasticsearch')->getSection('elasticsearch');
        $backend->setIndex($resourceConfig->get('index_pattern', 'logstash-*'));
        
        return $backend;
    }
}
