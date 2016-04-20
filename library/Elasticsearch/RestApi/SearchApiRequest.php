<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

class SearchApiRequest extends RestApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';

    /**
     * The search API endpoint
     */
    const ENDPOINT = '_search';

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
     * Create a new SearchApiRequest
     *
     * @param   array   $indices    The patterns defining the indices where to search for documents
     * @param   array   $types      The names of the document types to search for
     * @param   array   $data       The body for this search request
     */
    public function __construct(array $indices = null, array $types = null, array $data = null)
    {
        $this->types = $types;
        $this->indices = $indices;
        $this->setPayload($data, $data !== null ? 'application/json' : null);
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if ($this->path === null) {
            if (empty($this->indices)) {
                if (empty($this->types)) {
                    $this->path = static::ENDPOINT;
                } else {
                    $this->path = sprintf('*/%s/%s', join(',', $this->types), static::ENDPOINT);
                }
            } elseif (empty($this->types)) {
                $this->path = sprintf('%s/%s', join(',', $this->indices), static::ENDPOINT);
            } else {
                $this->path = sprintf(
                    '%s/%s/%s',
                    join(',', $this->indices),
                    join(',', $this->types),
                    static::ENDPOINT
                );
            }
        }

        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        $payload = parent::getPayload();
        if ($payload !== null) {
            $payload = $this->jsonEncode($payload);
        }

        return $payload;
    }
}
