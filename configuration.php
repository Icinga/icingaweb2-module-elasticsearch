<?php

$section = $this->menuSection('Logstash')
    ->setUrl('logstash')
    ->setIcon('doc-text');

$section->add(t('Event search'))
        ->setIcon('search')
        ->setUrl('logstash/event/search');

