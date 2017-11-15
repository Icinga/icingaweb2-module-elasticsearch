<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Controllers;

use Icinga\Module\Elasticsearch\Controller;
use Icinga\Module\Elasticsearch\Elastic;
use Icinga\Module\Elasticsearch\Instances;

class DocumentsController extends Controller
{
    public function indexAction()
    {
        $index = $this->params->getRequired('index');
        $type = $this->params->getRequired('type');
        $id = $this->params->getRequired('id');

        $instance = (new Instances())
            ->select()
            ->where('name', $this->params->getRequired('instance'))
            ->fetchRow();

        if ($instance === false) {
            $this->httpNotFound($this->translate('Instance not found'));
        }

        $this->setTitle($this->translate('Document'));

        $document = (new Elastic($instance))
            ->select()
            ->get("{$index}/{$type}/{$id}");

        $this->view->document = $document;
    }
}
