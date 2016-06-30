<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Event;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;

use Icinga\Exception\IcingaException;

class EventsController extends Controller
{
    public function indexAction()
    {
        $this->createTabs('events', 'list');

        $type = $this->getParam('type');
        if ($type === null) {
            throw new IcingaException('You need to specify a type to list!');
        }

        // TODO: control permissions

        $eventType = EventTypeRepository::load($type);

        $this->view->title = $eventType->getLabel();
        $this->view->description = $eventType->getDescription();
        $this->view->fields = $eventType->getFields();

        $query = $eventType->getEventQuery();

        $sort_columns = array();
        foreach ($query->getColumns() as $value) {
            $sort_columns[$value] = $value;
        }

        $filterColumns = $query->getFilterColumns();
        if(($key = array_search('type', $filterColumns)) !== false) {
            unset($filterColumns[$key]);
        }

        $this->setupFilterControl($query, $filterColumns, null, array('type', 'fields', 'refresh'));
        $this->setupLimitControl(100);
        $this->setupSortControl($sort_columns, $query, array('@timestamp' => 'desc'));
        $this->setupPaginationControl($query, 100);
        $this->setupAutoRefresherControl();

        $this->view->eventUrl = $this->view->url('elasticsearch/events/show', array(
            'type' => $type,
        ));

        $this->view->events = $query;
    }
    
    public function showAction()
    {
        $this->createTabs('event', 'show');

        $type = $this->getParam('type');
        if ($type === null) {
            throw new IcingaException('You need to specify a type to show events from!');
        }

        $id = $this->getParam('id');
        if ($id === null) {
            throw new IcingaException('You need to specify the event id!');
        }

        $this->view->eventType = $eventType = EventTypeRepository::load($type);
        $this->view->event = Event::fromRepository($eventType->getEventQuery(), $id);
    }
}