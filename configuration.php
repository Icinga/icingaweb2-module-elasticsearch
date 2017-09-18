<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

/** @var Icinga\Application\Modules\Module $this */

$this->providePermission(
    'elasticsearch/config',
    $this->translate('Allow to configure Elasticsaerch instances and event types')
);

$this->provideRestriction(
    'elasticsearch/eventtypes',
    $this->translate('Restrict the event types the user may use')
);

$this->provideConfigTab('elasticsearch/instances', array(
    'title' => $this->translate('Configure Elasticsearch Instances'),
    'label' => $this->translate('Elasticsearch Instances'),
    'url'   => 'instances'
));

$this->provideConfigTab('elasticsearch/eventtypes', array(
    'title' => $this->translate('Configure Event Types'),
    'label' => $this->translate('Event Types'),
    'url'   => 'eventtypes'
));
