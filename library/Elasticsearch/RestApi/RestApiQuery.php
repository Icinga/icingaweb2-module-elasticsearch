<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;
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
     * The name of the field used to unfold the result
     *
     * @var string
     */
    protected $unfoldAttribute;

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
     * Set the field to be used to unfold the result
     *
     * @param   string  $field
     *
     * @return  $this
     */
    public function setUnfoldAttribute($field)
    {
        $this->unfoldAttribute = $field;
        return $this;
    }

    /**
     * Return the field to use to unfold the result
     *
     * @return  string
     */
    public function getUnfoldAttribute()
    {
        return $this->unfoldAttribute;
    }

    /**
     * Return the limit for this query
     *
     * This will return a modified version of the actual limit in case attribute unfolding has been enabled.
     *
     * @param   bool    $ignoreUnfoldAttribute      Pass true to return the actual limit
     *
     * @return int|null
     */
    public function getLimit($ignoreUnfoldAttribute = false)
    {
        if ($this->unfoldAttribute === null || $ignoreUnfoldAttribute || !$this->hasOffset()) {
            return parent::getLimit();
        } elseif ($this->hasLimit()) {
            $limit = ($this->limitOffset / $this->limitCount + 1) * $this->limitCount;
            if ($this->peekAhead) {
                $limit += 1;
            }

            return $limit;
        } else {
            return $this->limitCount;
        }
    }

    /**
     * Return the offset for this query
     *
     * This will return a offset of zero in case attribute unfolding has been enabled.
     *
     * @param   bool    $ignoreUnfoldAttribute      Pass true to return the actual limit
     *
     * @return int|null
     */
    public function getOffset($ignoreUnfoldAttribute = false)
    {
        return $this->unfoldAttribute === null || $ignoreUnfoldAttribute ? parent::getOffset() : 0;
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
        if ($this->unfoldAttribute === null) {
            return new CountApiRequest(
                $this->getIndices(),
                $this->getTypes(),
                array('query' => $this->ds->renderFilter($this->getFilter()))
            );
        }

        $requestedFields = $this->getColumns();
        if (isset($requestedFields[$this->unfoldAttribute])) {
            $realUnfoldAttribute = $requestedFields[$this->unfoldAttribute];
        } elseif (in_array($this->unfoldAttribute, $requestedFields, true)) {
            $realUnfoldAttribute = $this->unfoldAttribute;
        } else {
            throw new LogicException('The field used to unfold a query\'s result must be selected');
        }

        $request = new SearchApiRequest(
            $this->getIndices(),
            $this->getTypes(),
            array(
                'query' => $this->ds->renderFilter($this->getFilter()),
                'aggs'  => array(
                    'unfolded_count' => array(
                        'value_count' => array(
                            'field' => $realUnfoldAttribute
                        )
                    )
                )
            )
        );

        $request->getParams()
            ->add('_source', 'false')
            ->add('filter_path', 'aggregations');
        return $request;
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
        if ($this->unfoldAttribute === null) {
            return $json['count'];
        } else {
            return $json['aggregations']['unfolded_count']['value'];
        }
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

        if ($this->unfoldAttribute !== null) {
            if (isset($fields[$this->unfoldAttribute])) {
                $realUnfoldAttribute = $fields[$this->unfoldAttribute];
            } elseif (in_array($this->unfoldAttribute, $fields, true)) {
                $realUnfoldAttribute = $this->unfoldAttribute;
            } else {
                throw new LogicException('The field used to unfold a query\'s result must be selected');
            }

            $body['highlight']['fields'][$realUnfoldAttribute] = array(
                'pre_tags'              => array(''),
                'post_tags'             => array(''),
                'require_field_match'   => true,
                'number_of_fragments'   => 0
            );
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
        $requestedFields = $this->getColumns();
        $offset = $this->getOffset(true);
        $limit = $this->getLimit(true);

        $count = 0;
        $result = array();
        $json = $response->json();
        foreach ($json['hits']['hits'] as $hit) {
            $rows = $this->ds->createRow($hit, $requestedFields, $this->unfoldAttribute);
            if (! is_array($rows)) {
                $count += 1;
                if ($offset === 0 || $offset < $count) {
                    $result[] = $rows;
                }

                if ($limit > 0 && $limit === count($result)) {
                    return $result;
                }
            } else {
                $realUnfoldAttribute = isset($requestedFields[$this->unfoldAttribute])
                    ? $requestedFields[$this->unfoldAttribute]
                    : $this->unfoldAttribute;

                $matchedValues = null;
                if (isset($hit['highlight'][$realUnfoldAttribute])) {
                    $matchedValues = array_map('strtolower', $hit['highlight'][$realUnfoldAttribute]);
                    unset($hit['highlight'][$realUnfoldAttribute]);
                }

                foreach ($rows as $row) {
                    if (empty($matchedValues) ||
                        in_array(strtolower($row->{$this->unfoldAttribute}), $matchedValues, true)
                    ) {
                        $count += 1;
                        if ($offset === 0 || $offset < $count) {
                            $result[] = $row;
                        }

                        if ($limit > 0 && $limit === count($result)) {
                            return $result;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
