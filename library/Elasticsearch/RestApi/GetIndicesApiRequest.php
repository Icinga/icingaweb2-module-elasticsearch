<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

class GetIndicesApiRequest extends RestApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';

    /**
     * The indices to work with
     *
     * @var array
     */
    protected $indices;

    /**
     * The settings to retrieve
     *
     * @var array
     */
    protected $settings;

    /**
     * Create a new GetIndicesApiRequest
     *
     * @param   array   $indices    The indices to work with
     * @param   array   $settings   The settings to retrieve
     */
    public function __construct(array $indices, array $settings = null)
    {
        $this->indices = $indices;
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = sprintf('/%s', join(',', $this->indices));
            if (! empty($this->settings)) {
                $this->path .= '/' . join(',', $this->settings);
            }
        }

        return $this->path;
    }
}
