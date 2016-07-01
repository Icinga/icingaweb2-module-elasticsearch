<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Event;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;

use Icinga\Exception\IcingaException;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Object\Host;

class HostController extends Controller
{
    public function init()
    {
        $this->assertPermission('elasticsearch/host');
    }

    protected function buildTabs(Host $host, $type)
    {
        $tabs = $this->getTabs();

        $tabs->add('host', array(
            'title' => sprintf('%s: %s', $this->translate('Host'), $host->getName()),
            'url' => $this->view->url('monitoring/host/show', array(
                'host' => $host->getName(),
            )),
        ));

        $eventTypes = new EventTypeRepository();
        $eventTypeQuery = $eventTypes->select();
        $this->filterEventTypes($eventTypeQuery);

        foreach ($eventTypeQuery as $eventType) {
            $tabs->add($eventType->name, array(
                'title' => $eventType->label ?: $eventType->name,
                'url' => $this->view->url('elasticsearch/host', array(
                    'host' => $host->getName(),
                    'type' => $eventType->name,
                )),
            ));
        }
        if ($tabs->get($type) !== null) {
            $tabs->activate($type);
        }

    }

    public function indexAction()
    {

        // load the host
        $this->view->object = $host = new Host($this->monitoringBackend(), $this->params->getRequired('host'));
        if ($host->fetch() === false) {
            throw new NotFoundError('Host not found');
        }

        $type = $this->getParam('type');

        $this->buildTabs($host, $type);

        if ($type !== null) {
            $this->assertEventType($type);

            $this->view->eventType = $eventType = EventTypeRepository::load($type);

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

            $this->setupFilterControl($query, $filterColumns, null, array('type', 'host', 'fields', 'refresh'));
            $this->setupLimitControl(100);
            $this->setupSortControl($sort_columns, $query, array('@timestamp' => 'desc'));
            $this->setupPaginationControl($query, 100);
            $this->setupAutoRefresherControl();

            $this->view->eventUrl = $this->view->url('elasticsearch/events/show', array(
                'type' => $type,
            ));

            $this->view->events = $query;
        }
    }
    
    public function showAction()
    {
        $this->createTabs('event', 'show');

        $type = $this->getParam('type');
        if ($type === null) {
            throw new IcingaException('You need to specify a type to show events from!');
        }
        $this->assertEventType($type);

        $id = $this->getParam('id');
        if ($id === null) {
            throw new IcingaException('You need to specify the event id!');
        }

        $this->view->eventType = $eventType = EventTypeRepository::load($type);
        $this->view->event = Event::fromRepository($eventType->getEventQuery(), $id);
    }

    /* TODO: re-implement
    / **
     * @todo move code into Event
     * @throws IcingaException
     * /
    public function ackAction() {
        $index = $this->_getParam('index');
        $type = $this->_getParam('type');
        $id = $this->_getParam('id');
        $comment = $this->_getParam('comment');
        $action = $this->_getParam('comment_action', null);

        $username = "testuser"; //TODO: get icingaweb's username

        $data = array(
            'icinga_comments' => array(
                array(
                    'username'  => $username,
                    'comment'   => $comment,
                    'timestamp' => gmstrftime('%Y-%m-%d %H:%M:%S')
                )
            )
        );

        if ($action == 'ack')
            $data['icinga_acknowledge'] = 1;
        elseif ($action == 'unack')
            $data['icinga_acknowledge'] = 0;

        // fetch the event
        $repository = EventBackend::fromConfig();
        $event = new Event($repository->getDataSource());

        $event->setIndex($index);
        $event->setType($type);
        $event->setId($id);

        if ($event->fetch() === false) {
            throw new IcingaException("Event not found! index=%s type=%s id=%s", $index, $type, $id);
        }

        $document = $event->getDocument();
        if (isset($document->icinga_comments)) {
            $data['icinga_comments'] = array_merge($document->icinga_comments, $data['icinga_comments']);
        }

        $event->update_partial($data);

        $url = $this->view->url('elasticsearch/event/show', array(
            'index' => $index,
            'type' => $type,
            'id' => $id
        ));
        $this->redirectNow($url);
    }
    */
}