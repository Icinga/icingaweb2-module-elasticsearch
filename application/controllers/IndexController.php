<?php
/* Icinga Web 2 - Logstash Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Logstash\Controllers;

use Icinga\Module\Logstash\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->redirectNow('logstash/event/search');
    }
}