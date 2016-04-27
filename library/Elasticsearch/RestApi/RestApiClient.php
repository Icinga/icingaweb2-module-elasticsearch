<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use ArrayIterator;
use Icinga\Application\Benchmark;
use LogicException;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Selectable;
use Icinga\Data\Updatable;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotImplementedError;
use Icinga\Exception\StatementException;
use Icinga\Exception\QueryException;
use Icinga\Module\Elasticsearch\Exception\RestApiException;

class RestApiClient implements Extensible, Reducible, Selectable, Updatable
{
    /**
     * The cURL handle of this RestApiClient
     *
     * @var resource
     */
    protected $curl;

    /**
     * The host of the API
     *
     * @var string
     */
    protected $host;

    /**
     * The name of the user to access the API with
     *
     * @var string
     */
    protected $user;

    /**
     * The password for the user the API is accessed with
     *
     * @var string
     */
    protected $pass;

    /**
     * The path of a file holding one or more certificates to verify the peer with
     *
     * @var string
     */
    protected $certificatePath;

    /**
     * Create a new RestApiClient
     *
     * @param   string  $host               The host of the API
     * @param   string  $user               The name of the user to access the API with
     * @param   string  $pass               The password for the user the API is accessed with
     * @param   string  $certificatePath    The path of a file holding one or more certificates to verify the peer with
     */
    public function __construct($host, $user = null, $pass = null, $certificatePath = null)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->certificatePath = $certificatePath;
    }

    /**
     * Return the cURL handle of this RestApiClient
     *
     * @return  resource
     */
    public function getConnection()
    {
        if ($this->curl === null) {
            $this->curl = $this->createConnection();
        }

        return $this->curl;
    }

    /**
     * Create and return a new cURL handle for this RestApiClient
     *
     * @return  resource
     */
    protected function createConnection()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->certificatePath !== null) {
            curl_setopt($curl, CURLOPT_CAINFO, $this->certificatePath);
        }

        if ($this->user !== null && $this->pass !== null) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->pass);
        }

        return $curl;
    }

    /**
     * Send the given request and return its response
     *
     * @param   RestApiRequest  $request
     *
     * @return  RestApiResponse
     *
     * @throws  RestApiException            In case an error occured while handling the request
     */
    public function request(RestApiRequest $request)
    {
        $scheme = strpos($this->host, '://') !== false ? '' : 'http://';
        $path = '/' . ltrim($request->getPath(), '/');
        $query = ($request->getParams()->isEmpty() ? '' : ('?' . (string) $request->getParams()));

        $curl = $this->getConnection();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $request->getHeaders());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($curl, CURLOPT_URL, $scheme . $this->host . $path . $query);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getPayload());

        $result = curl_exec($curl);
        if ($result === false) {
            $restApiException = new RestApiException(curl_error($curl));
            $restApiException->setErrorCode(curl_errno($curl));
            throw $restApiException;
        }

        $response = new RestApiResponse(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        if ($result) {
            $response->setPayload($result);
            $response->setContentType(curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        }

        return $response;
    }

    /**
     * Create and return a new query for this RestApiClient
     *
     * @param   array   $indices    An array of index name patterns
     * @param   array   $types      An array of document type names
     *
     * @return  RestApiQuery
     */
    public function select(array $indices = null, array $types = null)
    {
        $query = new RestApiQuery($this);
        if ($indices !== null) {
            $query->setIndices($indices);
        }
        if ($types !== null) {
            $query->setTypes($types);
        }
        return $query;
    }

    /**
     * Fetch and return all documents of the given query's result set using an iterator
     *
     * @param   RestApiQuery    $query  The query returning the result set
     *
     * @return  ArrayIterator
     */
    public function query(RestApiQuery $query)
    {
        return new ArrayIterator($this->fetchAll($query));
    }

    /**
     * Count all documents of the result set
     *
     * @param   RestApiQuery    $query
     *
     * @return  int
     */
    public function count(RestApiQuery $query)
    {
        $request = new CountApiRequest(
            $query->getIndices(),
            $query->getTypes(),
            array('query' => $this->renderFilter($query->getFilter()))
        );

        $response = $this->request($request);
        if (! $response->isSuccess()) {
            throw new QueryException($this->renderErrorMessage($response));
        }

        $json = $response->json();
        return $json['count'];
    }

    /**
     * Retrieve an array containing all documents of the result set
     *
     * @param   RestApiQuery    $query
     *
     * @return  array
     */
    public function fetchAll(RestApiQuery $query)
    {
        $response = $this->request($query->createSearchRequest());
        if (! $response->isSuccess()) {
            throw new QueryException($this->renderErrorMessage($response));
        }

        $json = $response->json();
        $result = array();
        foreach ($json['hits']['hits'] as $hit) {
            $result[] = $this->createRow($hit, $query->getColumns());
        }

        return $result;
    }

    /**
     * Merge meta fields and source fields together and return them as simple object
     *
     * Applies column aliases and sets null for each missing attribute.
     *
     * @param   array   $hit
     * @param   array   $requestedFields
     *
     * @return  object
     */
    public function createRow(array $hit, array $requestedFields)
    {
        // In case the hit contains attributes with a differing case than the requested fields, it is necessary
        // to create another array to map attributes case insensitively to their requested counterparts.
        // This does also apply the virtual alias handling. (Since Elasticsearch does not handle such)
        $loweredFieldMap = array();
        foreach ($requestedFields as $alias => $name) {
            $loweredName = strtolower($name);
            if (isset($loweredFieldMap[$loweredName])) {
                if (! is_array($loweredFieldMap[$loweredName])) {
                    $loweredFieldMap[$loweredName] = array($loweredFieldMap[$loweredName]);
                }

                $loweredFieldMap[$loweredName][] = is_string($alias) ? $alias : $name;
            } else {
                $loweredFieldMap[$loweredName] = is_string($alias) ? $alias : $name;
            }
        }

        $source = null;
        $fields = array();
        foreach ($hit as $metaField => $metaValue) {
            if ($metaField === '_source') {
                $source = $metaValue;
            } elseif (empty($loweredFieldMap)) {
                $fields[$metaField] = $metaValue;
            } elseif (isset($loweredFieldMap[strtolower($metaField)])) {
                $requestedFieldName = $loweredFieldMap[strtolower($metaField)];
                if (is_array($requestedFieldName)) {
                    foreach ($requestedFieldName as $requestedName) {
                        $fields[$requestedName] = $metaValue;
                    }
                } else {
                    $fields[$requestedFieldName] = $metaValue;
                }
            }
        }

        if (! empty($source)) {
            foreach ($source as $fieldName => $fieldValue) {
                if (empty($loweredFieldMap)) {
                    $fields[$fieldName] = $fieldValue;
                } elseif (isset($loweredFieldMap[strtolower($fieldName)])) {
                    // TODO: What about wildcard patterns?
                    $requestedFieldName = $loweredFieldMap[strtolower($fieldName)];
                    if (is_array($requestedFieldName)) {
                        foreach ($requestedFieldName as $requestedName) {
                            $fields[$requestedName] = $fieldValue;
                        }
                    } else {
                        $fields[$requestedFieldName] = $fieldValue;
                    }
                }
            }
        }

        // The hit may not contain all requested fields, so populate the
        // fields with the missing ones and their value being set to null
        foreach ($requestedFields as $alias => $name) {
            if (! is_string($alias)) {
                $alias = $name;
            }

            if (! array_key_exists($alias, $fields)) {
                $fields[$alias] = null;
            }
        }

        return (object) $fields;
    }

    /**
     * Fetch the first document of the result set
     *
     * @param   RestApiQuery    $query
     *
     * @return  array|false
     */
    public function fetchRow(RestApiQuery $query)
    {
        $clonedQuery = clone $query;
        $clonedQuery->limit(1);
        $results = $this->fetchAll($clonedQuery);
        return array_shift($results) ?: false;
    }

    /**
     * Fetch the first field of all documents of the result set as an array
     *
     * @param   RestApiQuery    $query
     *
     * @return  array
     *
     * @throws  LogicException      In case no attribute is being requested
     */
    public function fetchColumn(RestApiQuery $query)
    {
        $fields = $query->getColumns();
        if (empty($fields)) {
            throw new LogicException('You must request at least one attribute when fetching a single field');
        }

        $results = $this->fetchAll($query);
        $alias = key($fields);
        $field = is_int($alias) ? current($fields) : $alias;
        $values = array();
        foreach ($results as $document) {
            if (isset($document->$field)) {
                $values[] = $document->$field;
            }
        }

        return $values;
    }

    /**
     * Fetch the first field of the first document of the result set
     *
     * @param   RestApiQuery    $query
     *
     * @return  string
     */
    public function fetchOne(RestApiQuery $query)
    {
        throw new NotImplementedError('RestApiClient::fetchOne() is not implemented yet');
    }

    /**
     * Fetch all documents of the result set as an array of key-value pairs
     *
     * The first field is the key, the second field is the value.
     *
     * @param   RestApiQuery    $query
     *
     * @return  array
     */
    public function fetchPairs(RestApiQuery $query)
    {
        throw new NotImplementedError('RestApiClient::fetchPairs() is not implemented yet');
    }

    /**
     * Fetch and return the given document
     *
     * In case you are only interested in the source, pass "_source" as the only desired field.
     *
     * @param   string  $index          The index the document is located in
     * @param   string  $documentType   The type of the document to fetch
     * @param   string  $id             The id of the document to fetch
     * @param   array   $fields         The desired fields to return instead of all fields
     *
     * @return  object|false            Returns false in case no document could be found
     */
    public function fetchDocument($index, $documentType, $id, array $fields = null)
    {
        $request = new GetApiRequest($index, $documentType, $id);
        if (! empty($fields)) {
            if (count($fields) == 1 && $fields[0] === '_source') {
                $request->setSourceOnly();
                $fields = null;
            } else {
                $request->getParams()->add('_source', join(',', $fields));
            }
        }

        $response = $this->request($request);
        if (! $response->isSuccess()) {
            if ($response->getStatusCode() === 404) {
                return false;
            }

            throw new QueryException($this->renderErrorMessage($response));
        }

        $json = $response->json();
        return $this->createRow($json, $fields ?: array());
    }

    /**
     * Insert the given data for the given target
     *
     * @param   string|array    $target
     * @param   array           $data
     * @param   bool            $refresh    Whether to refresh the index
     *
     * @return  bool                    Whether the document has been created or not
     *
     * @throws  StatementException
     */
    public function insert($target, array $data, $refresh = true)
    {
        if (is_string($target)) {
            $target = explode('/', $target);
        }

        switch (count($target)) {
            case 3:
                list($index, $documentType, $id) = $target;
                break;
            case 2:
                list($index, $documentType) = $target;
                $id = null;
                break;
            default:
                throw new LogicException('Invalid target "%s"', join('/', $target));
        }

        $request = new IndexApiRequest($index, $documentType, $id, $data);
        if ($refresh) {
            $request->getParams()->add('refresh');
        }

        try {
            $response = $this->request($request);
        } catch (RestApiException $e) {
            throw new StatementException(
                'Failed to index document "%s". An error occurred: %s',
                join('/', $target),
                $e
            );
        }

        if (! $response->isSuccess()) {
            throw new StatementException(
                'Unable to index document "%s": %s',
                join('/', $target),
                $this->renderErrorMessage($response)
            );
        }

        $json = $response->json();
        return $json['created'];
    }

    /**
     * Update the target with the given data and optionally limit the affected documents by using a filter
     *
     * Note that the given filter will have no effect in case the target represents a single document.
     *
     * @param   string|array    $target
     * @param   array           $data
     * @param   Filter          $filter
     * @param   bool            $refresh    Whether to refresh the index
     *
     * @return  array   The updated document
     *
     * @throws  StatementException
     *
     * @todo    Add support for bulk updates
     */
    public function update($target, array $data, Filter $filter = null, $refresh = true)
    {
        if (is_string($target)) {
            $target = explode('/', $target);
        }

        switch (count($target)) {
            case 3:
                list($index, $documentType, $id) = $target;
                break;
            case 2:
                if ($filter === null) {
                    throw new LogicException('Update requests without id are required to provide a filter');
                }

                list($index, $documentType) = $target;
                $id = null;
                break;
            default:
                throw new LogicException('Invalid target "%s"', join('/', $target));
        }

        if ($id !== null) {
            $request = new UpdateApiRequest($index, $documentType, $id, array('doc' => $data));
            $request->getParams()->add('fields', '_source');
        } elseif ($filter !== null) {
            $query = new RestApiQuery($this, array('_id'));
            $ids = $query->setFilter($filter)->fetchColumn();
            if (empty($ids)) {
                throw new StatementException('No documents found');
            } elseif (count($ids) == 1) {
                $request = new UpdateApiRequest($index, $documentType, $ids[0], array('doc' => $data));
                $request->getParams()->add('fields', '_source');
            } else {
                throw new NotImplementedError('Bulk updates are not supported yet');
            }
        }

        if ($refresh) {
            $request->getParams()->add('refresh');
        }

        try {
            $response = $this->request($request);
        } catch (RestApiException $e) {
            throw new StatementException(
                'Failed to update document "%s". An error occurred: %s',
                join('/', $target),
                $e
            );
        }

        if (! $response->isSuccess()) {
            throw new StatementException(
                'Unable to update document "%s": %s',
                join('/', $target),
                $this->renderErrorMessage($response)
            );
        }

        $json = $response->json();
        return $json['get']['_source'];
    }

    /**
     * Delete documents in the given target, optionally limiting the affected documents by using a filter
     *
     * Note that the given filter will have no effect in case the target represents a single document.
     *
     * @param   string|array    $target
     * @param   Filter          $filter
     * @param   bool            $refresh    Whether to refresh the index
     *
     * @throws  StatementException
     *
     * @todo    Add support for bulk deletions
     */
    public function delete($target, Filter $filter = null, $refresh = true)
    {
        if (is_string($target)) {
            $target = explode('/', $target);
        }

        switch (count($target)) {
            case 3:
                list($index, $documentType, $id) = $target;
                break;
            case 2:
                if ($filter === null) {
                    throw new LogicException('Update requests without id are required to provide a filter');
                }

                list($index, $documentType) = $target;
                $id = null;
                break;
            default:
                throw new LogicException('Invalid target "%s"', join('/', $target));
        }

        if ($id !== null) {
            $request = new DeleteApiRequest($index, $documentType, $id);
        } elseif ($filter !== null) {
            $query = new RestApiQuery($this, array('_id'));
            $ids = $query->setFilter($filter)->fetchColumn();
            if (empty($ids)) {
                throw new StatementException('No documents found');
            } elseif (count($ids) == 1) {
                $request = new DeleteApiRequest($index, $documentType, $ids[0]);
            } else {
                throw new NotImplementedError('Bulk deletions are not supported yet');
            }
        }

        if ($refresh) {
            $request->getParams()->add('refresh');
        }

        try {
            $response = $this->request($request);
        } catch (RestApiException $e) {
            throw new StatementException(
                'Failed to delete document "%s". An error occurred: %s',
                join('/', $target),
                $e
            );
        }

        if (! $response->isSuccess()) {
            throw new StatementException(
                'Unable to delete document "%s": %s',
                join('/', $target),
                $this->renderErrorMessage($response)
            );
        }
    }

    /**
     * Render and return a human readable error message for the given error document
     *
     * @return  string
     *
     * @todo    Parse Elasticsearch 2.x structured errors
     */
    public function renderErrorMessage(RestApiResponse $response)
    {
        try {
            $errorDocument = $response->json();
        } catch (IcingaException $e) {
            return sprintf('%s: %s',
                $e->getMessage(),
                $response->getPayload()
            );
        }

        if (! isset($errorDocument['error'])) {
            return sprintf('Elasticsearch unknown json error %s: %s',
                $response->getStatusCode(),
                $response->getPayload()
            );
        }

        if (is_array($errorDocument)) {
            return sprintf('Elasticsearch json error %s: %s',
                $response->getStatusCode(),
                json_encode($errorDocument['error'])
            );
        }
        else {
            return $response->getPayload();
        }
    }

    /**
     * Render and return the given filter as Elasticsearch query
     *
     * @param   Filter  $filter
     *
     * @return  array
     */
    public function renderFilter(Filter $filter)
    {
        $renderer = new FilterRenderer($filter);
        return $renderer->getQuery();
    }

    /**
     * Retrieve columns from the Elasticsearch indices.
     *
     * It will get you a merged list of columns available over the specified indices and types.
     *
     * @param   string  $index  The index or index pattern to get
     * @param   array   $types  An array of types to get columns for
     *
     * @throws  QueryException  When Elasticsearch returns an error
     *
     * @return  array           A list of column names
     *
     * @todo    Do a cached retrieval?
     */
    public function fetchColumns($index, $types = array())
    {
        Benchmark::measure('Retrieving columns for types: ' . (!empty($types) ? join(', ', $types) : '(all)'));
        $request = new MappingApiRequest($index, $types);

        $response = $this->request($request);
        if (! $response->isSuccess()) {
            if ($response->getStatusCode() === 404) {
                return false;
            }

            throw new QueryException($this->renderErrorMessage($response));
        }

        // initialize with interal columns
        $columns = array(
            '_index',
            '_type',
            '_id',
        );

        foreach ($response->json() as $index => $mappings) {
            if (! array_key_exists('mappings', $mappings)) {
                continue;
            }
            foreach ($mappings['mappings'] as $type) {
                if (! array_key_exists('properties', $type)) {
                    continue;
                }
                foreach ($type['properties'] as $column => $detail) {
                    if ($column === '@version') {
                        continue;
                    }
                    if (array_key_exists('properties', $detail)) {
                        // ignore structured types
                        // TODO: support this later?
                        continue;
                    }
                    if (! in_array($column, $columns)) {
                        $columns[] = $column;
                    }
                }

            }
        }
        Benchmark::measure('Finished retrieving columns');

        return $columns;
    }
}
