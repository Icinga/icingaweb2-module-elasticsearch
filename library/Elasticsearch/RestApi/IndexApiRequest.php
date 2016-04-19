<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

class IndexApiRequest extends DocumentApiRequest
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
            return sprintf('/%s/%s', $this->index, $this->documentType);
        }

        return sprintf('/%s/%s/%s/_create', $this->index, $this->documentType, $this->id);
    }
}
