<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class GetApiRequest extends DocumentApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        if ($this->id === null) {
            throw new LogicException('GetApiRequest is missing a document id');
        }

        return sprintf('/%s/%s/%s', $this->index, $this->documentType, $this->id);
    }
}
