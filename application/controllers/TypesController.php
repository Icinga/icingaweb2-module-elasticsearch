<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Exception\IcingaException;
use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Forms\Types\EventTypeForm;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;
use Icinga\Web\Url;

class TypesController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/elasticsearch');
    }

    public function indexAction()
    {
        $this->createTabs('types', 'index');
        $repository = new EventTypeRepository();
        $this->view->eventTypes = $repository->select();
    }

    public function editAction()
    {
        $name = $this->getParam('type');
        if ($name === null) {
            throw new IcingaException('You need to specify a type to edit!');
        }

        $this->createTabs('events', 'edit');

        $form = new EventTypeForm();
        $form->setRedirectUrl(Url::fromPath('elasticsearch/events', array('type' => $name)));
        $form->setRepository(new EventTypeRepository());
        $form->edit($name)->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }
    
    public function createAction()
    {
        $this->createTabs('events', 'create');

        $form = new EventTypeForm();
        //$form->setRedirectUrl(Url::fromPath('elasticsearch/events/list', array('type' => $name)));
        $form->setRedirectUrl(Url::fromPath('elasticsearch/types'));
        $form->setRepository(new EventTypeRepository());
        $form->add()->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    public function removeAction()
    {
        $name = $this->getParam('type');
        if ($name === null) {
            throw new IcingaException('You need to specify a type to edit!');
        }

        $this->createTabs('events', 'edit');

        $form = new EventTypeForm();
        $form->setRedirectUrl(Url::fromPath('elasticsearch/types'));
        $form->setRepository(new EventTypeRepository());
        $form->remove($name)->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }
}