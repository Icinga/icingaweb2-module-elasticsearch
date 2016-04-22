<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use Icinga\Data\SimpleQuery;

class RestApiQuery extends SimpleQuery
{
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
}
