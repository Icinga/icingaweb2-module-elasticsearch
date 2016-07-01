<?php

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Monitoring\Web\Hook\HostActionsHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Url;

class HostActions extends HostActionsHook
{
    public function getActionsForHost(Host $host)
    {
        return $this->createNavigation(array(
            mt('elasticsearch', 'Elasticsearch Events') => array(
                'url'  => Url::fromPath('elasticsearch/host', array('host' => $host->getName())),
                'icon' => 'doc-text',
                'permission' => 'elasticsearch/host',
            )
        ));
    }
}
