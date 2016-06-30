<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Exception\IcingaException;
use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Event;
use Icinga\Module\Elasticsearch\EventBackend;

class SearchController extends Controller
{
    public function indexAction()
    {
        $this->assertPermission('module/elasticsearch/search');
        
        $this->createTabs('search', 'search');

        $repository = EventBackend::fromConfig();

        $query = $repository->select();

        $sort_columns = array();
        foreach ($query->getColumns() as $value) {
            $sort_columns[$value] = $value;
        }
        $this->setupFilterControl($query, null, null, array('fields', 'refresh'));
        $this->setupLimitControl(100);
        $this->setupSortControl($sort_columns, $query, array('@timestamp' => 'desc'));
        $this->setupPaginationControl($query, 100);
        $this->setupFieldSelectorControl($query);
        $this->setupAutoRefresherControl();

        $this->view->eventUrl = $this->view->url('elasticsearch/search/show');

        $this->view->events = $query;
    }

    public function showAction()
    {
        $this->assertPermission('module/elasticsearch/search');
 
        $this->createTabs('event', 'show');

        $id = $this->getParam('id');
        if ($id === null) {
            throw new IcingaException('You need to specify the event id!');
        }

        $repository = EventBackend::fromConfig();
        $query = $repository->select();

        $this->view->event = Event::fromRepository($query, $id);
        
        $this->render('events/show', null, true);
    }
}
