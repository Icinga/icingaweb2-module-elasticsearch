<?php

use Icinga\Module\Logstash\Api;
use Icinga\Web\Controller\ModuleActionController;
use Icinga\Web\Url;

class Logstash_TestController extends ModuleActionController
{
    public function searchAction()
    {
        $this->view->compact = $this->_getParam('view') === 'compact';
        if ($this->_request->isPost()) {
            $this->redirectNow(Url::fromRequest()->setParam('min_severity', $this->_getParam('min_severity'))->setParam('program', $this->_getParam('program'))->setParam('message', $this->getParam('message')));
        }

        $this->view->live = $this->params->shift('live');
        $this->view->program = $this->params->shift('program');
        $this->view->message = $this->params->shift('message');

        if ($this->view->live) {
            $this->setAutorefreshInterval(1);
        } else {
            $this->setAutorefreshInterval(15);
        }
        $this->view->minSeverity = (int) $this->_getParam('min_severity', 4);
        $this->view->host = $this->_getParam('host');
        $this->view->configFile = $this->Config()->getConfigFile();

        $hostname = $this->Config()->get('elk', 'hostname');
        $protocol = $this->Config()->get('elk', 'protocol', 'http');
        $port = (int) $this->Config()->get('elk', 'port', 9200);
        $index_pattern = $this->Config()->get('elk', 'index_pattern', 'logstash-*');

        if ($hostname) {
            $url = sprintf(
                '%s://%s:%d/%s/',
                $protocol,
                $hostname,
                $port,
                $index_pattern
            );
            $api = new Api($url);
            $this->view->result = $api->sampleSearch(
                $this->view->host,
                $this->view->program,
                $this->view->message,
                $this->view->minSeverity
            );
            $this->view->base_url = $url;
        }
    }
}
