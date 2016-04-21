<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class DeleteApiRequest extends DocumentApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'DELETE';

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        if ($this->id === null) {
            throw new LogicException('DeleteApiRequest is missing a document id');
        }

        return sprintf('/%s/%s/%s', $this->index, $this->documentType, $this->id);
    }
}
