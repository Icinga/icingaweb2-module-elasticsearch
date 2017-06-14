<?php

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Web\Hook\ServiceActionsHook;
use Icinga\Web\Url;

class ServiceActions extends ServiceActionsHook
{
    public function getActionsForService(Service $service)
    {
        $elements = array();

        $elements[mt('elasticsearch', 'Elasticsearch Events')] = array(
            'url'  => Url::fromPath('elasticsearch/host', array('host' => $service->getHost()->getName())),
            'icon' => 'doc-text',
            'permission' => 'elasticsearch/host',
        );

        /* TODO: re-implement
        if ($service->check_command == "logstash_events") {
            // TODO: add icon when Icingaweb2 supports it
            // <i class="icon-doc-text"></i>
            $elements[mt('logstash', 'Logstash events')] = array(
             Url::fromPath('elasticsearch/event/list', array(
                    'host' => $service->getHost()->getName(),
                    'service' => $service->getName()
                )
            );
        }
        */

        return $this->createNavigation($elements);
    }
}
