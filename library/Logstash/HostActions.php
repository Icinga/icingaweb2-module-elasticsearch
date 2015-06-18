<?php

namespace Icinga\Module\Logstash;

use Icinga\Module\Monitoring\Web\Hook\HostActionsHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Url;

class HostActions extends HostActionsHook
{
    public function getActionsForHost(Host $host)
    {
        return array(
            'Syslog' => Url::fromPath('logstash/event/search', array('host' => $host->host_name))
        );
    }
}
