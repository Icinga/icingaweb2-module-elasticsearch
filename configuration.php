<?php

use Icinga\Application\Modules\Module;
/** @var Module $this */

$this->providePermission('module/elasticsearch/search', $this->translate('Allow unrestricted access to query data in Elasticsearch'));

$section = $this->menuSection('Elasticsearch')
    ->setIcon('doc-text')
    ->setUrl('elasticsearch');

$section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('elasticsearch/search');

$this->provideConfigTab('elasticsearch', array(
    'title' => $this->translate('Configure Elasticsearch settings'),
    'label' => $this->translate('Elasticsearch'),
    'url' => 'config/elasticsearch'
));
