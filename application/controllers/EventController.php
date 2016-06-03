<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Exception\IcingaException;
use Icinga\Exception\NotImplementedError;

use Icinga\Module\Elasticsearch\Controller;

use Icinga\Module\Elasticsearch\EventBackend;
use Icinga\Module\Elasticsearch\Event;

use Icinga\Module\Monitoring\Backend;
//use Icinga\Module\Monitoring\Object\Host;
//use Icinga\Module\Monitoring\Object\Service;

class EventController extends Controller
{
    public function indexAction()
    {
        $this->redirectNow('elasticsearch/event/search');
    }

    public function searchAction()
    {
        $repository = EventBackend::fromConfig();

        if ($type = $this->getParam('type')) {
            $repository->setBaseTable($type);
        }

        $query = $repository->select();

        $sort_columns = array();
        foreach ($query->getColumns() as $value) {
            $sort_columns[$value] = $value;
        }
        $this->setupFilterControl($query, null, null, array('fields'));
        $this->setupLimitControl(100);
        $this->setupSortControl($sort_columns, $query, array('@timestamp' => 'desc'));
        $this->setupPaginationControl($query, 100);
        $this->setupFieldSelectorControl($query);

        $this->getTabs()->add('search', array(
            'title' => $this->translate('Events'),
            'url'   => $this->view->url()
        ))->activate(('search'));;

        $this->view->live = $this->params->shift('live');
        if ($this->view->live) {
            $this->setAutorefreshInterval(1);
        } else {
            $this->setAutorefreshInterval(15);
        }

        /* TODO: adapt to FilterEditor
        $this->view->show_ack = $this->_getParam('show_ack', 0);

        $this->view->warning = $this->_getParam('warning');
        if ($this->view->warning) {
            $search->setIcingaWarningQuery($this->view->warning);
        }
        $this->view->critical = $this->_getParam('critical');
        if ($this->view->critical) {
            $search->setIcingaCriticalQuery($this->view->critical);
        }
        */

        // TODO: reimplement
        //if (isset($fields))
        //    $search->setFields($fields);


        // TODO: reimplement
        //if (! $this->view->show_ack)
        //    $search->setWithoutAck(true);


        $this->view->events = $query->fetchAll();

        // TODO: reimplement
        //$this->view->warnings = $search->getIcingaWarningCount();
        //$this->view->criticals = $search->getIcingaCriticalCount();
    }

    public function showAction() {

        $index = $this->_getParam('index');
        $type = $this->_getParam('type');
        $id = $this->_getParam('id');

        $this->getTabs()->add('show', array(
            'title' => $this->translate('Event detail'),
            'url'   => $this->view->url()
        ))->activate(('show'));

        $repository = EventBackend::fromConfig();
        $event = new Event($repository->getDataSource());

        $event->setIndex($index);
        $event->setType($type);
        $event->setId($id);

        $this->view->event = $event->fetch();
    }

    /**
     * @todo move code into Event
     * @throws IcingaException
     */
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

    /**
     * @todo reimplement with log-types!
     * @throws IcingaException
     * @throws \Icinga\Exception\Http\HttpNotFoundException
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function listAction() {
        throw new NotImplementedError('list action is currently not implemented!');
        /*
        $host_name = $this->_getParam('host');
        $service_name = $this->_getParam('service');

        if (!$host_name or !$service_name)
            throw new IcingaException('host and service are required params!');

        // TODO: monitoring enabled?
        $backend = Backend::createBackend();

        $service = new Service($backend, $host_name, $service_name);

        if (!$service->fetch())
            throw new IcingaException('Service could not be found!');

        $cv = (array) $service->customvars;

        // data from service
        if (isset($cv['logstash_query']))
            $this->view->query = $cv['logstash_query'];
        else throw new IcingaException('Could not find cv logstash_query!');

        $this->view->filter = null;
        if (isset($cv['logstash_filter']))
            $this->view->filter = $cv['logstash_filter'];

        $this->view->fields = null;
        if (isset($cv['logstash_fields']))
            $this->view->fields = $cv['logstash_fields'];

        $this->view->warning = null;
        if (isset($cv['logstash_warning']))
            $this->view->warning = $cv['logstash_warning'];

        $this->view->critical = null;
        if (isset($cv['logstash_critical']))
            $this->view->critical = $cv['logstash_critical'];

        // query elasticsearch
        $client = new RestApiClient($this->elasticsearch_url);
        $query = $client->select(array($this->index_pattern));

        $query->setQueryString($this->view->query);
        $query->setFilterQueryString($this->view->filter);

        if ($this->view->warning)
            $query->setIcingaWarningQuery($this->view->warning);
        if ($this->view->critical)
            $query->setIcingaCriticalQuery($this->view->critical);
        $query->setFilteredByIcingaQueries(true);

        // extra params
        $this->view->show_ack = $this->_getParam('show_ack', 0);
        if (! $this->view->show_ack)
            $query->setWithoutAck(true);

        $limit = $this->_getParam('limit', 100);
        $page = $this->_getParam('page', 1);
        $query->limit($limit, $limit * ($page-1));

        // other params and view setup
        $this->view->compact = $this->_getParam('view') === 'compact';

        $this->view->live = $this->params->shift('live');
        if ($this->view->live) {
            $this->setAutorefreshInterval(1);
        } else {
            $this->setAutorefreshInterval(15);
        }

        $this->view->fieldlist = array();
        if ($this->view->fields) {
            $this->view->fieldlist = preg_split('#\s*[,]\s*#', $this->view->fields);
        }

        $this->view->limiter = new Limiter();
        $this->view->limiter->setDefaultLimit(100);

        $this->view->paginator = new Paginator();
        $this->view->paginator->setQuery($query);

        $this->view->events = $query->fetchAll();
        $this->view->count = $query->count();
        $this->view->took = $query->getTook();
        $this->view->warnings = $query->getIcingaWarningCount();
        $this->view->criticals = $query->getIcingaCriticalCount();

        if ($page > 1 and count($this->view->events) == 0) {
            $this->redirectNow($this->view->url()->without( 'page'));
        }

        $this->getTabs()->add('list', array(
            'title' => $this->translate('Events'),
            'url'   => $this->view->url()
        ))->activate(('list'));;
        */
    }
}
