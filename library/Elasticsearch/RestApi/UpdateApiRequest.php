<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class UpdateApiRequest extends DocumentApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'POST';

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        if ($this->id === null) {
            throw new LogicException('UpdateApiRequest is missing a document id');
        }

        return sprintf('/%s/%s/%s/_update', $this->index, $this->documentType, $this->id);
    }
}
