<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Web\Hook\HostActionsHook;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Url;

class HostActions extends HostActionsHook
{
    public function getActionsForHost(Host $host)
    {
        return $this->createNavigation([
            mt('elasticsearch', 'Elasticsearch Events') => [
                'icon'          => 'doc-text',
                'permission'    => 'elasticsearch/events',
                'url'           => Url::fromPath('elasticsearch/events', ['host' => $host->getName()])
            ]
        ]);
    }
}
