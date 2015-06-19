<?php

namespace Icinga\Module\Logstash;

use Icinga\Web\Controller as IcingaWebController;
use Exception;

class Controller extends IcingaWebController
{
    protected $elasticsearch_url;

    public function moduleInit() {
        $this->elasticsearch_url = $this->Config()->get('elasticsearch', 'url');
        if ($index_pattern = $this->Config()->get('elasticsearch', 'index_pattern', null)) {
            $this->elasticsearch_url .= "/".$index_pattern;
        }

        if (!$this->elasticsearch_url) {
            $this->view->configFile = $this->Config()->getConfigFile();
            $this->render('error-config', null, true);
            throw new Exception("No elasticsearch URL configured!");
        }
    }

}
