<?php

use Icinga\Module\Logstash\Search;
use Icinga\Web\Controller\ModuleActionController;

use Icinga\Web\Widget\Limiter;
use Icinga\Web\Widget\Paginator;

class Logstash_EventController extends ModuleActionController
{
    public function init()
    {
        parent::init();
        $tabs = $this->getTabs();

        $tabs->add('Events', array(
            'title' => $this->translate('Events'),
            'url'   => 'logstash/event/search'
        ));
    }

    public function indexAction() {
        $this->redirectNow('logstash/event/search');
    }

    public function searchAction()
    {
        $this->view->compact = $this->_getParam('view') === 'compact';

        $this->view->live = $this->params->shift('live');
        if ($this->view->live) {
            $this->setAutorefreshInterval(1);
        } else {
            $this->setAutorefreshInterval(15);
        }

        $this->view->query = $this->_getParam('query');
        $this->view->filter = $this->_getParam('filter');

        $this->view->fields = $this->_getParam('fields');
        $this->view->fieldlist = array();
        if ($this->view->fields) {
            $split = preg_split('/\s*;\s*/', $this->view->fields, 2);
            $this->view->fieldlist = preg_split('/\s*[,]\s*/', $split[0]);
            $this->view->detaillist = count($split) > 1 ? preg_split('/\s*[,]\s*/', $split[1]) : [];
        }

        $this->view->configFile = $this->Config()->getConfigFile();
        $hostname = $this->Config()->get('elk', 'hostname');
        $protocol = $this->Config()->get('elk', 'protocol', 'http');
        $port = (int) $this->Config()->get('elk', 'port', 9200);
        $index_pattern = $this->Config()->get('elk', 'index_pattern', 'logstash-*');

        $url = sprintf(
            '%s://%s:%d/%s/',
            $protocol,
            $hostname,
            $port,
            $index_pattern
        );

        $search = new Search($url);
        if ($this->view->query) {
            $search->setQueryString($this->view->query);

            if ($this->view->filter)
                $search->setFilterQueryString($this->view->filter);

            $limit = $this->_getParam('limit', 100);
            $page = $this->_getParam('page', 1);
            $search->limit($limit, $limit * ($page-1));

            $this->view->limiter = new Limiter();
            $this->view->limiter->setDefaultLimit(100);

            $this->view->paginator = new Paginator();
            $this->view->paginator->setQuery($search);

            // sort_field
            // sort_dir

            // from
            // size
            $search->search();

            $this->view->search = $search;
        }
        $this->view->base_url = $url;
    }
}
