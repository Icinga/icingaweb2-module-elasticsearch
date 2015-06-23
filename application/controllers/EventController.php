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
}
