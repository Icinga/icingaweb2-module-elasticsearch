<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Logstash\Controllers;

use Icinga\Module\Logstash\Controller;
use Icinga\Module\Logstash\Forms\Config\ElasticsearchConfigForm;

/**
 * Configuration controller for the module
 */
class ConfigController extends Controller
{
    protected $ignore_elasticsearch = true;
    
    public function indexAction()
    {
        $this->redirectNow('logstash/config/elasticsearch');
    }

    /**
     * Configure elasticsearch settings for the module
     */
    public function elasticsearchAction()
    {
        $form = new ElasticsearchConfigForm();
        $form->setIniConfig($this->Config());
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('elasticsearch');
    }
}
