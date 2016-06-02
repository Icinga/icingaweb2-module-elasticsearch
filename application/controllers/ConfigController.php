<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Forms\Config\ElasticsearchConfigForm;

/**
 * Configuration controller for the module
 */
class ConfigController extends Controller
{
    public function indexAction()
    {
        $this->redirectNow('elasticsearch/config/elasticsearch');
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
