<?php

use Icinga\Module\Logstash\Controller;
use Icinga\Module\Logstash\Search;
use Icinga\Module\Logstash\Event;

use Icinga\Web\Widget\Limiter;
use Icinga\Web\Widget\Paginator;

class Logstash_EventController extends Controller
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

        $search = new Search($this->elasticsearch_url."/".$this->index_pattern);

        $this->view->warning = $this->_getParam('warning');
        if ($this->view->warning) {
            $search->parseIcingaQueryString('warning', $this->view->warning);
        }
        $this->view->critical = $this->_getParam('critical');
        if ($this->view->critical) {
            $search->parseIcingaQueryString('critical', $this->view->critical);
        }

        if ($this->view->query) {
            $search->setQueryString($this->view->query);

            if (isset($fields))
                $search->setFields($fields);

            if ($this->view->filter)
                $search->setFilterQueryString($this->view->filter);

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
}
