<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;

class IndexController extends Controller
{
    public function indexAction()
    {
        // TODO: has config permission
        $this->view->configAdmin = true;
        
        $this->view->eventTypes = EventTypeRepository::loadAll();
        $this->createTabs('main', 'overview');
    }
}