<?php

use Icinga\Web\Controller\ModuleActionController;


class Logstash_IndexController extends ModuleActionController
{

    public function indexAction() {
        $this->redirectNow('logstash/event/search');
    }

}