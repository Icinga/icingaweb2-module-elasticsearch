<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Repository;

use InvalidArgumentException;
use LogicException;

use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\StatementException;
use Icinga\Repository\Repository;
use Icinga\Repository\RepositoryQuery;
use Icinga\Module\Elasticsearch\RestApi\RestApiClient;

abstract class ElasticsearchRepository extends Repository implements Extensible, Reducible, Updatable
{
    /**
     * The datasource being used
     *
     * @var RestApiClient
     */
    protected $ds;

    /**
     * The index this repository is linked to
     *
     * @var string
     */
    protected $index;

    /**
     * Create a new Elasticsearch repository object
     *
     * @param   RestApiClient   $ds     The datasource to use
     */
    public function __construct(RestApiClient $ds)
    {
        parent::__construct($ds);
    }

    /**
     * Set the index to link to this repository
     *
     * @param   string  $index
     *
     * @return  $this
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * Return the index this repository is linked to
     *
     * @return  string
     */
    public function getIndex()
    {
        if ($this->index === null) {
            throw new LogicException('Missing index');
        }

        return $this->index;
    }

    /**
     * Return the given document type with its index being applied
     *
     * @param   string|array    $documentType
     *
     * @return  array
     */
    protected function applyIndex($documentType)
    {
        if (! is_array($documentType)) {
            $documentType = array($this->getIndex(), $documentType);
        } elseif (! empty($documentType)) {
            array_unshift($documentType, $this->getIndex());
        } else {
            throw new LogicException('Missing document type');
        }

        return $documentType;
    }

    /**
     * Extract and return the document type from the given target
     *
     * @param   string|array    $target
     *
     * @return  string
     */
    protected function extractDocumentType($target)
    {
        if (is_array($target)) {
            if (empty($target)) {
                throw new LogicException('Missing document type');
            }

            $target = $target[0];
        } elseif (! is_string($target)) {
            throw new InvalidArgumentException(sprintf('Invalid target "%s" given', print_r($target, true)));
        }

        return $target;
    }

    /**
     * Resolve the given columns supposed to be fetched
     *
     * @param   string  $documentType
     * @param   array   $desiredFields
     *
     * @return  array
     */
    protected function prepareFields($documentType, array $desiredFields = null)
    {
        if (empty($desiredFields)) {
            return $this->requireAllQueryColumns($documentType);
        }

        $fields = array();
        foreach ($desiredFields as $field) {
            $fields[] = $this->requireQueryColumn($documentType, $field);
        }

        return $fields;
    }

    /**
     * Fetch and return the given document
     *
     * @param   string  $documentType   The type of the document to fetch
     * @param   string  $id             The id of the document to fetch
     * @param   array   $fields         The desired fields to return instead of all fields
     *
     * @return  object|false            Returns false in case no document could be found
     */
    public function fetchDocument($documentType, $id, array $fields = null)
    {
        return $this->ds->fetchDocument(
            $this->getIndex(),
            $this->requireTable($documentType),
            $id,
            $this->prepareFields($documentType, $fields)
        );
    }

    /**
     * Insert the given document as the given document type
     *
     * @param   string|array    $documentType
     * @param   array           $document
     * @param   bool            $refresh        Whether to refresh the index
     *
     * @return  bool    Whether the document has been created or not
     *
     * @throws  StatementException
     */
    public function insert($documentType, array $document, $refresh = true)
    {
        if (is_string($documentType)) {
            $documentType = explode('/', $documentType);
        }

        $this->requireTable($documentType);
        return $this->ds->insert(
            $this->applyIndex($documentType),
            $this->requireStatementColumns($this->extractDocumentType($documentType), $document),
            $refresh
        );
    }

    /**
     * Update documents of the given type and optionally limit the affected documents by using a filter
     *
     * @param   string|array    $documentType
     * @param   array           $document
     * @param   Filter          $filter
     * @param   bool            $refresh        Whether to refresh the index
     *
     * @return  array   The updated document
     *
     * @throws  StatementException
     */
    public function update($documentType, array $document, Filter $filter = null, $refresh = true)
    {
        if (is_string($documentType)) {
            $documentType = explode('/', $documentType);
        }

        $this->requireTable($documentType);

        if ($filter) {
            $filter = $this->requireFilter($this->extractDocumentType($documentType), $filter);
        }

        return $this->ds->update(
            $this->applyIndex($documentType),
            $this->requireStatementColumns($this->extractDocumentType($documentType), $document),
            $filter,
            $refresh
        );
    }

    /**
     * Delete documents of the given type, optionally limiting the affected documents by using a filter
     *
     * @param   string|array    $documentType
     * @param   Filter          $filter
     * @param   bool            $refresh        Whether to refresh the index
     *
     * @throws  StatementException
     */
    public function delete($documentType, Filter $filter = null, $refresh = true)
    {
        if (is_string($documentType)) {
            $documentType = explode('/', $documentType);
        }

        $this->requireTable($documentType);

        if ($filter) {
            $filter = $this->requireFilter($this->extractDocumentType($documentType), $filter);
        }

        return $this->ds->delete($this->applyIndex($documentType), $filter, $refresh);
    }

    /**
     * Validate that the requested document type exists
     *
     * @param   string|array        $documentType   The document type to validate
     * @param   RepositoryQuery     $query          An optional query to pass as context
     *
     * @return  array   The document type with its index being applied
     */
    public function requireTable($documentType, RepositoryQuery $query = null)
    {
        if ($query !== null) {
            $query->getQuery()->setIndices(array($this->getIndex()));
        }

        return parent::requireTable($this->extractDocumentType($documentType), $query);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveQueryColumnAlias($table, $alias)
    {
        // TODO: Fixed in the current master. Remove this once a new release of Web 2 is out.
        if (is_int($alias)) {
            $queryColumns = $this->getQueryColumns();
            return $queryColumns[$table][$alias];
        }

        return parent::resolveQueryColumnAlias($table, $alias);
    }
}
