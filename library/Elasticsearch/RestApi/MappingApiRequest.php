<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

class MappingApiRequest extends RestApiRequest
{
    /**
     * The indices to work with
     *
     * @var array
     */
    protected $indices;

    /**
     * The types to work with
     *
     * @var array
     */
    protected $types;

    /**
     * The fields to work with
     *
     * @var array
     */
    protected $fields;

    /**
     * Create a new MappingApiRequest
     *
     * @param   array   $indices    The indices to work with
     * @param   array   $types      The types to work with
     * @param   array   $fields     The fields to work with
     * @param   array   $data       The index, mapping or field settings to send
     */
    public function __construct(array $indices, array $types, array $fields = null, array $data = null)
    {
        $this->indices = $indices;
        $this->types = $types;
        $this->fields = $fields;
        $this->setPayload($data, $data !== null ? 'application/json' : null);
    }

    /**
     * Create and return the path for this MappingApiRequest
     *
     * @return  string
     */
    protected function createPath()
    {
        if (! empty($this->indices)) {
            $path = sprintf('/%s/_mappings', join(',', $this->indices));
        } else {
            $path = '/_mappings';
        }

        if (! empty($this->types)) {
            $path .= '/' . join(',', $this->types);
        }

        if (! empty($this->indices) && !empty($this->fields)) {
            $path .= '/field/' . join(',', $this->fields);
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = $this->createPath();
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
