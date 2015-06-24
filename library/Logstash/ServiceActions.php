<?php

namespace Icinga\Module\Logstash;

use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Web\Hook\ServiceActionsHook;
use Icinga\Web\Url;

class ServiceActions extends ServiceActionsHook
{
    public function getActionsForService(Service $service)
    {
        if ($service->check_command == "logstash_events") {
            return array(
                '<i class="icon-doc-text"></i>Logstash' => Url::fromPath('logstash/event/list', array(
                        'host' => $service->getHost()->getName(),
                        'service' => $service->getName()
                    )
                )
            );
        }
        else return array();
    }
}
