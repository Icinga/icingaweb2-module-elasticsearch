<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use ArrayIterator;
use LogicException;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Selectable;
use Icinga\Data\Updatable;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotImplementedError;
use Icinga\Exception\StatementException;

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
        curl_setopt($curl, CURLOPT_URL, $scheme . $path . $query);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getPayload());

        $result = curl_exec($curl);
        if ($result === false) {
            throw new RestApiException(curl_errno($curl), curl_error($curl));
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
        throw new NotImplementedError('Queries are not supported yet');
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
        throw new NotImplementedError('Queries are not supported yet');
    }

    /**
     * Insert the given data for the given target
     *
     * @param   string|array    $target
     * @param   array           $data
     *
     * @return  bool                    Whether the document has been created or not
     *
     * @throws  StatementException
     */
    public function insert($target, array $data)
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

        try {
            $response = $this->request(new IndexApiRequest($index, $documentType, $id, $data));
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
     *
     * @throws  StatementException
     *
     * @todo    Add support for filters and bulk updates
     */
    public function update($target, array $data, Filter $filter = null)
    {
        if ($filter !== null) {
            throw new NotImplementedError('Update requests with filter are not supported yet');
        }

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

        try {
            $response = $this->request(new UpdateApiRequest($index, $documentType, $id, $data));
        } catch (RestApiException $e) {
            throw new StatementException(
                'Failed to update document "%s". An error occurred: %s',
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
    }

    /**
     * Delete entries in the given target, optionally limiting the affected entries by using a filter
     *
     * Note that the given filter will have no effect in case the target represents a single document.
     *
     * @param   string|array    $target
     * @param   Filter          $filter
     *
     * @throws  StatementException
     */
    public function delete($target, Filter $filter = null)
    {
        throw new NotImplementedError('Deletions are not supported yet');
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
            return $response->getPayload();
        }

        if (! isset($errorDocument['error'])) {
            return $response->getPayload();
        }

        return $errorDocument['error'];
    }
}
