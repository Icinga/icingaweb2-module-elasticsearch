<?php

$section = $this->menuSection('Elasticsearch')
    ->setIcon('doc-text');

$section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('elasticsearch/event/search');

$this->provideConfigTab('elasticsearch', array(
    'title' => $this->translate('Configure Elasticsearch settings'),
    'label' => $this->translate('Elasticsearch'),
    'url' => 'config/elasticsearch'
));
