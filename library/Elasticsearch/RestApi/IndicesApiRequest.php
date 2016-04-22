<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class IndicesApiRequest extends RestApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';

    /**
     * The index or index pattern to request from
     *
     * @var string
     */
    protected $index;

    /**
     * Creates a new IndicesApiRequest.
     *
     * @param   string  $index  The index name or pattern
     */
    public function __construct($index)
    {
        if (strpos($index, '_') === 0) {
            throw new LogicException('an index must not begin with an underscore!');
        }

        $this->index = $index;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        return sprintf('/%s', $this->index);
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

}
