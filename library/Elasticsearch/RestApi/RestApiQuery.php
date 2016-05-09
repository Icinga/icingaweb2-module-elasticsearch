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
        $foldedColumn = $this->getUnfoldAttribute();
        if ($foldedColumn === null) {
            return new CountApiRequest(
                $this->getIndices(),
                $this->getTypes(),
                array('query' => $this->ds->renderFilter($this->getFilter()))
            );
        }

        $requestedFields = $this->getColumns();
        if (isset($requestedFields[$foldedColumn])) {
            $foldedField = $requestedFields[$foldedColumn];
        } elseif (in_array($foldedColumn, $requestedFields, true)) {
            $foldedField = $foldedColumn;
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
                            'field' => $foldedField
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
        if ($this->getUnfoldAttribute() === null) {
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

        if ($this->getUnfoldAttribute() !== null) {
            foreach ($this->foldedHighlights() as $field) {
                $body['highlight']['fields'][$field] = array(
                    'pre_tags'              => array(''),
                    'post_tags'             => array(''),
                    'require_field_match'   => true,
                    'number_of_fragments'   => 0
                );
            }

            $body['from'] = 0;
            if ($this->hasOffset() && $this->hasLimit()) {
                $limit = ($this->limitOffset / $this->limitCount + 1) * $this->limitCount;
                if ($this->peekAhead) {
                    $limit += 1;
                }

                $body['size'] = $limit;
            }
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
        $foldedColumn = $this->getUnfoldAttribute();
        $requestedFields = $this->getColumns();
        $offset = $this->getOffset();
        $limit = $this->getLimit();

        $count = 0;
        $result = array();
        $json = $response->json();
        foreach ($json['hits']['hits'] as $hit) {
            $hit = new SearchHit($hit);
            if ($foldedColumn === null) {
                $result[] = $hit->createRow($requestedFields);
            } else {
                foreach ($hit->createRows($requestedFields, $foldedColumn) as $row) {
                    $matches = true;
                    if (isset($hit['highlight'])) {
                        foreach ($this->foldedHighlights() as $column => $field) {
                            if (isset($hit['highlight'][$field])) {
                                $value = $row->{$foldedColumn};
                                if (is_string($column) && is_object($value)) {
                                    $value = $value->{$column};
                                }

                                if (! in_array($value, $hit['highlight'][$field], true)) {
                                    $matches = false;
                                    break;
                                }
                            }
                        }
                    }

                    if ($matches) {
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

    /**
     * Return the highlight columns of an unfolded row
     *
     * @return  array
     */
    protected function foldedHighlights()
    {
        $requestedFields = $this->getColumns();
        $foldedColumn = $this->getUnfoldAttribute();
        if (isset($requestedFields[$foldedColumn])) {
            $highlightFields = array($requestedFields[$foldedColumn]);
        } elseif (in_array($foldedColumn, $requestedFields, true)) {
            $highlightFields = array($foldedColumn);
        } else {
            $highlightFields = array();
            foreach ($requestedFields as $alias => $field) {
                if (is_string($alias) && strpos($alias, '.') !== false) {
                    list($parent, $child) = explode('.', $alias, 2);
                } elseif (strpos($field, '.') !== false) {
                    list($parent, $child) = explode('.', $field, 2);
                } else {
                    continue;
                }

                if ($parent === $foldedColumn) {
                    $highlightFields[$child] = $field;
                }
            }
        }

        return $highlightFields;
    }
}
