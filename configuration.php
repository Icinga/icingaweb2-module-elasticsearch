<?php

/** @var Icinga\Application\Modules\Module $this */

use Icinga\Authentication\Auth;
$auth = Auth::getInstance();

$this->providePermission(
    'elasticsearch/search',
    $this->translate('Allow unrestricted access to query data in Elasticsearch')
);

$this->providePermission(
    'elasticsearch/events',
    $this->translate('Allow listing of events based on configured event types')
);

$this->providePermission(
    'elasticsearch/host',
    $this->translate('Allow listing of events for hosts')
);

$this->provideRestriction(
    'elasticsearch/events/allowed_types',
    $this->translate('Restrict the types the user may use')
);

$section = $this->menuSection('Elasticsearch')
    ->setIcon('doc-text')
    ->setUrl('elasticsearch');

if ($auth->hasPermission('elasticsearch/search'))
{
    $section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('elasticsearch/search');
}

if ($auth->hasPermission('config/elasticsearch'))
{
    $section->add(t('Event Types'))
        ->setIcon('sliders')
        ->setUrl('elasticsearch/types');
}

$this->provideConfigTab('elasticsearch', array(
    'title' => $this->translate('Configure Elasticsearch settings'),
    'label' => $this->translate('Elasticsearch'),
    'url' => 'config/elasticsearch'
));
