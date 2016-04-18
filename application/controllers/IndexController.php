<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->redirectNow('logstash/event/search');
    }
}