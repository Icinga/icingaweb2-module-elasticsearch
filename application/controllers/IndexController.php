<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;

class IndexController extends Controller
{
    public function indexAction()
    {
        $this->createTabs('main', 'overview');

        $this->view->configAdmin = $this->hasPermission('config/elasticsearch');
        $this->view->canTypes = $this->hasPermission('elasticsearch/events');

        $repository = new EventTypeRepository();
        $query = $repository->select();
        $this->filterEventTypes($query);
        $this->view->eventTypes = $query;
    }
}