<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Forms\InstanceConfigForm;
use Icinga\Module\Elasticsearch\Instances;
use Icinga\Web\Url;

class InstancesController extends Controller
{
    public function init()
    {
        $this->assertPermission('elasticsearch/config');
    }

    public function indexAction()
    {
        $this->setTitle($this->translate('Instances'));

        $this->getTabs()->add(uniqid(), [
            'label'     => $this->translate('Event Types'),
            'url'       => Url::fromPath('elasticsearch/eventtypes')
        ]);

        $this->view->instances = (new Instances())->select(['name', 'uri']);
    }

    public function newAction()
    {
        $form = new InstanceConfigForm([
            'mode'  => InstanceConfigForm::MODE_INSERT
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('New Instance'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }

    public function updateAction()
    {
        $name = $this->params->getRequired('instance');

        $form = new InstanceConfigForm([
            'mode'          => InstanceConfigForm::MODE_UPDATE,
            'identifier'    => $name
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('Update Instance'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }

    public function deleteAction()
    {
        $name = $this->params->getRequired('instance');

        $form = new InstanceConfigForm([
            'mode'          => InstanceConfigForm::MODE_DELETE,
            'identifier'    => $name
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('Remove Instance'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }
}
