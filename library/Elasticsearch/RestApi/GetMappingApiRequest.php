<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

class GetMappingApiRequest extends MappingApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';
}
