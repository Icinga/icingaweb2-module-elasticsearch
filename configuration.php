<?php

$section = $this->menuSection('Logstash')
    ->setIcon('doc-text');

$section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('logstash/event/search');
