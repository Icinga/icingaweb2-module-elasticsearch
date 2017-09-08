<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

/** @var Icinga\Application\Modules\Module $this */

$this->providePermission(
    'elasticsearch/config',
    $this->translate('Allow to configure Elasticsaerch instances and event types')
);
