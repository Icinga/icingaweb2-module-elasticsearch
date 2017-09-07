<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Repository\IniRepository;

class Eventtypes extends IniRepository
{
    protected $configs = [
        'eventtypes' => [
            'name'      => 'eventtypes',
            'keyColumn' => 'name',
            'module'    => 'elasticsearch'
        ]
    ];

    protected $queryColumns = [
        'eventtypes' => [
            'name',
            'instance',
            'index',
            'filter',
            'fields'
        ]
    ];
}
