<?php

namespace Icinga\Module\Logstash;

use Icinga\Web\Controller as IcingaWebController;
use Exception;

class Controller extends IcingaWebController
{
    protected $elasticsearch_url;
    protected $index_pattern;

    public function moduleInit() {
        $this->elasticsearch_url = $this->Config()->get('elasticsearch', 'url');
        $this->index_pattern = $this->Config()->get('elasticsearch', 'index_pattern', 'logstash-*');

        if (!$this->elasticsearch_url) {
            $this->view->configFile = $this->Config()->getConfigFile();
            $this->render('error-config', null, true);
            throw new Exception("No elasticsearch URL configured!");
        }
    }

}
