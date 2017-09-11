<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Repository\IniRepository;

class Instances extends IniRepository
{
    protected $configs = [
        'instances' => [
            'name'      => 'instances',
            'keyColumn' => 'name',
            'module'    => 'elasticsearch'
        ]
    ];

    protected $queryColumns = [
        'instances' => [
            'name',
            'uri',
            'user',
            'password'
        ]
    ];
}
