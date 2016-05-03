<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use Icinga\Data\SimpleQuery;

class RestApiQuery extends SimpleQuery
{
    /**
     * The default offset used by search requests
     */
    const DEFAULT_OFFSET = 0;

    /**
     * The default limit used by search requests
     */
    const DEFAULT_LIMIT = 10;

    /**
     * The patterns defining the indices where to search for documents
     *
     * @var array
     */
    protected $indices;

    /**
     * The names of the document types to search for
     *
     * @var array
     */
    protected $types;

    /**
     * Whether _source retrieval is disabled
     *
     * @var bool
     */
    protected $disabledSource;

    /**
     * Set the patterns defining the indices where to search for documents
     *
     * @param   array   $indices
     *
     * @return  $this
     */
    public function setIndices(array $indices = null)
    {
        $this->indices = $indices;
        return $this;
    }

    /**
     * Return the patterns defining the indices where to search for documents
     *
     * @return  array|null
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Set the names of the document types to search for
     *
     * @param   array   $types
     *
     * @return  $this
     */
    public function setTypes(array $types = null)
    {
        $this->types = $types;
        return $this;
    }

    /**
     * Return the names of the document types to search for
     *
     * @return  array|null
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Set whether _source retrieval is disabled
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function disableSourceRetrieval($state)
    {
        $this->disabledSource = (bool) $state;
        return $this;
    }

    /**
     * Return whether _source retrieval is disabled
     *
     * @return  bool
     */
    public function isSourceRetrievalDisabled()
    {
        return $this->disabledSource ?: false;
    }

    /**
     * Choose a document type and the fields you are interested in
     *
     * {@inheritdoc} This registers the given target as type filter.
     */
    public function from($target, array $fields = null)
    {
        $this->setTypes(array($target));
        return parent::from($target, $fields);
    }

    /**
     * Create and return a new instance of CountApiRequest for this query
     *
     * @return  CountApiRequest
     */
    public function createCountRequest()
    {
        return new CountApiRequest(
            $this->getIndices(),
            $this->getTypes(),
            array('query' => $this->ds->renderFilter($this->getFilter()))
        );
    }

    /**
     * Create and return the result for the given count response
     *
     * @param   RestApiResponse     $response
     *
     * @return  int
     */
    public function createCountResult(RestApiResponse $response)
    {
        $json = $response->json();
        return $json['count'];
    }

    /**
     * Create and return a new instance of SearchApiRequest for this query
     *
     * @return  SearchApiRequest
     */
    public function createSearchRequest()
    {
        $body = array(
            'from'  => $this->getOffset() ?: static::DEFAULT_OFFSET,
            'size'  => $this->hasLimit() ? $this->getLimit() : static::DEFAULT_LIMIT,
            'query' => $this->ds->renderFilter($this->getFilter())
        );
        if ($this->hasOrder()) {
            $sort = array();
            foreach ($this->getOrder() as $order) {
                $sort[] = array($order[0] => strtolower($order[1]));
            }

            $body['sort'] = $sort;
        }

        $fields = $this->getColumns();
        if ($this->isSourceRetrievalDisabled()) {
            $body['_source'] = false;
        } elseif (! empty($fields)) {
            $sourceFields = array();
            foreach ($fields as $fieldName) {
                if (substr($fieldName, 0, 1) !== '_') {
                    $sourceFields[] = $fieldName;
                }
            }

            $body['_source'] = empty($sourceFields) ? false : $sourceFields;
        }

        return new SearchApiRequest($this->getIndices(), $this->getTypes(), $body);
    }

    /**
     * Create and return a result set for the given search response
     *
     * @param   RestApiResponse     $response
     *
     * @return  array
     */
    public function createSearchResult(RestApiResponse $response)
    {
        $json = $response->json();
        $result = array();
        foreach ($json['hits']['hits'] as $hit) {
            $result[] = $this->ds->createRow($hit, $this->getColumns());
        }

        return $result;
    }
}
