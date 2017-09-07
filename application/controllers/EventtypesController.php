<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Eventtypes;
use Icinga\Module\Elasticsearch\Forms\EventtypeConfigForm;
use Icinga\Web\Url;

class EventtypesController extends Controller
{
    public function init()
    {
        $this->assertPermission('elasticsearch/config');
    }

    public function indexAction()
    {
        $this->getTabs()->add(uniqid(), [
            'label'     => $this->translate('Instances'),
            'url'       => Url::fromPath('elasticsearch/instances')
        ]);

        $this->setTitle($this->translate('Event Types'));

        $this->view->eventtypes = (new Eventtypes())->select(['name', 'instance', 'index', 'filter', 'fields']);
    }

    public function newAction()
    {
        $form = new EventtypeConfigForm([
            'mode'  => EventtypeConfigForm::MODE_INSERT
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('New Event Type'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }

    public function updateAction()
    {
        $name = $this->params->getRequired('eventtype');

        $form = new EventtypeConfigForm([
            'mode'          => EventtypeConfigForm::MODE_UPDATE,
            'identifier'    => $name
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('Update Event Type'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }

    public function deleteAction()
    {
        $name = $this->params->getRequired('eventtype');

        $form = new EventtypeConfigForm([
            'mode'          => EventtypeConfigForm::MODE_DELETE,
            'identifier'    => $name
        ]);

        $form->handleRequest();

        $this->setTitle($this->translate('Remove Event Type'));

        $this->view->form = $form;

        $this->_helper->viewRenderer->setRender('form', null, true);
    }
}
