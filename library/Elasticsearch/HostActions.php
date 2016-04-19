<?php

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Monitoring\Web\Hook\HostActionsHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Url;

class HostActions extends HostActionsHook
{
    public function getActionsForHost(Host $host)
    {
        return array();
        /* TODO
        return array(
            'Syslog' => Url::fromPath('elasticsearch/event/search', array('host' => $host->host_name))
        );
        */
    }
}
