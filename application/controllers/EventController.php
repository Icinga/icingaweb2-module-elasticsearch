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
        $this->assertPermission('module/elasticsearch/search');

        $repository = EventBackend::fromConfig();

        if ($type = $this->getParam('type')) {
            $repository->setBaseTable($type);
        }

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

        $this->getTabs()->add('search', array(
            'title' => $this->translate('Events'),
            'url'   => $this->view->url()
        ))->activate(('search'));;

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
        //if (! $this->view->show_ack)
        //    $search->setWithoutAck(true);

        $this->view->events = $query->fetchAll();

        // TODO: reimplement
        //$this->view->warnings = $search->getIcingaWarningCount();
        //$this->view->criticals = $search->getIcingaCriticalCount();
    }
}
