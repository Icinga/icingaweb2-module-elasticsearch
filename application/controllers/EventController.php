<?php

/* Icinga Web 2 - Logstash Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Logstash\Controllers;

use Icinga\Module\Logstash\Controller;

use Icinga\Module\Logstash\Search;
use Icinga\Module\Logstash\Event;

use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Widget\Limiter;
use Icinga\Web\Widget\Paginator;

class EventController extends Controller
{
    public function indexAction() {
        $this->redirectNow('logstash/event/search');
    }

    public function searchAction()
    {
        $this->getTabs()->add('search', array(
            'title' => $this->translate('Events'),
            'url'   => $this->view->url()
        ))->activate(('search'));;

        $this->view->compact = $this->_getParam('view') === 'compact';

        $this->view->live = $this->params->shift('live');
        if ($this->view->live) {
            $this->setAutorefreshInterval(1);
        } else {
            $this->setAutorefreshInterval(15);
        }

        $this->view->query = $this->_getParam('query');
        $this->view->filter = $this->_getParam('filter');

        $fields = null;
        $this->view->fields = $this->_getParam('fields');
        $this->view->fieldlist = array();
        if ($this->view->fields) {
            $this->view->fieldlist = $fields = preg_split('/\s*[,]\s*/', $this->view->fields);
        }

        $this->view->show_ack = $this->_getParam('show_ack', 0);

        $search = new Search($this->elasticsearch_url."/".$this->index_pattern);

        $this->view->warning = $this->_getParam('warning');
        if ($this->view->warning) {
            $search->setIcingaWarningQuery($this->view->warning);
        }
        $this->view->critical = $this->_getParam('critical');
        if ($this->view->critical) {
            $search->setIcingaCriticalQuery($this->view->critical);
        }

        if ($this->view->query) {
            $search->setQueryString($this->view->query);

            //if (isset($fields))
            //    $search->setFields($fields);

            if ($this->view->filter)
                $search->setFilterQueryString($this->view->filter);

            if (! $this->view->show_ack)
                $search->setWithoutAck(true);

            $limit = $this->_getParam('limit', 100);
            $page = $this->_getParam('page', 1);
            $search->limit($limit, $limit * ($page-1));

            $this->view->limiter = new Limiter();
            $this->view->limiter->setDefaultLimit(100);

            $this->view->paginator = new Paginator();
            $this->view->paginator->setQuery($search);

            $this->view->hits = $search->fetchAll();
            $this->view->count = $search->count();
            $this->view->took = $search->getTook();
            $this->view->warnings = $search->getIcingaWarningCount();
            $this->view->criticals = $search->getIcingaCriticalCount();

            if ($page > 1 and count($this->view->hits) == 0) {
                $this->redirectNow($this->view->url()->without( 'page'));
            }
        }
    }

    public function showAction() {

        $index = $this->_getParam('index');
        $type = $this->_getParam('type');
        $id = $this->_getParam('id');

        $this->getTabs()->add('show', array(
            'title' => $this->translate('Event detail'),
            'url'   => $this->view->url()
        ))->activate(('show'));

        $event = new Event($this->elasticsearch_url);

        $event->setIndex($index);
        $event->setType($type);
        $event->setId($id);

        $this->view->event = $event->fetch();
    }

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
        $event = new Event($this->elasticsearch_url);

        $event->setIndex($index);
        $event->setType($type);
        $event->setId($id);

        $event->fetch();

        if (property_exists($event->getSource(), 'icinga_comments')) {
            $data['icinga_comments'] = array_merge($event->getSource()->icinga_comments, $data['icinga_comments']);
        }

        if ($event->found() !== true)
            throw new Exception("Event not found! index=%s type=%s id=%s", $index, $type, $id);

        $event->update_partial($data);

        $url = $this->view->url('logstash/event/show', array(
            'index' => $index,
            'type' => $type,
            'id' => $id
        ));
        $this->redirectNow($url);
    }

    public function listAction() {
        $host_name = $this->_getParam('host');
        $service_name = $this->_getParam('service');

        if (!$host_name or !$service_name)
            throw new Exception('host and service are required params!');

        // TODO: monitoring enabled?
        $backend = Backend::createBackend();

        $service = new Service($backend, $host_name, $service_name);

        if (!$service->fetch())
            throw new Exception('Service could not be found!');

        $cv = (array) $service->customvars;
        
        // data from service
        if (isset($cv['logstash_query']))
            $this->view->query = $cv['logstash_query'];
        else throw new Exception('Could not find cv logstash_query!');

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

        // setup search
        $search = new Search($this->elasticsearch_url."/".$this->index_pattern);

        $search->setQueryString($this->view->query);
        $search->setFilterQueryString($this->view->filter);

        if ($this->view->warning)
            $search->setIcingaWarningQuery($this->view->warning);
        if ($this->view->critical)
            $search->setIcingaCriticalQuery($this->view->critical);
        $search->setFilteredByIcingaQueries(true);

        // extra params
        $this->view->show_ack = $this->_getParam('show_ack', 0);
        if (! $this->view->show_ack)
            $search->setWithoutAck(true);

        $limit = $this->_getParam('limit', 100);
        $page = $this->_getParam('page', 1);
        $search->limit($limit, $limit * ($page-1));

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
            $this->view->fieldlist = preg_split('/\s*[,]\s*/', $this->view->fields);
        }

        $this->view->limiter = new Limiter();
        $this->view->limiter->setDefaultLimit(100);

        $this->view->paginator = new Paginator();
        $this->view->paginator->setQuery($search);

        $this->view->hits = $search->fetchAll();
        $this->view->count = $search->count();
        $this->view->took = $search->getTook();
        $this->view->warnings = $search->getIcingaWarningCount();
        $this->view->criticals = $search->getIcingaCriticalCount();

        if ($page > 1 and count($this->view->hits) == 0) {
            $this->redirectNow($this->view->url()->without( 'page'));
        }

        $this->getTabs()->add('list', array(
            'title' => $this->translate('Events'),
            'url'   => $this->view->url()
        ))->activate(('list'));;

    }
}
