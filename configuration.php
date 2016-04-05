<?php

$section = $this->menuSection('Logstash')
    ->setIcon('doc-text');

$section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('logstash/event/search');

$this->provideConfigTab('elasticsearch', array(
    'title' => $this->translate('Configure Elasticsearch settings'),
    'label' => $this->translate('Elasticsearch'),
    'url' => 'config/elasticsearch'
));
