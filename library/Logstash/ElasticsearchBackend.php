<?php

namespace Icinga\Module\Logstash;

use Icinga\Module\Logstash\Curl;

abstract class ElasticsearchBackend {
    protected $elasticsearch;
    protected $curl;

    public function __construct($elasticsearch=null)
    {
        $this->curl = new Curl();

        if ($elasticsearch) {
            $this->curl->setBaseURL($elasticsearch);
        }
    }

    /**
     * @return String url
     */
    public function getElasticsearch()
    {
        return $this->curl->getBaseURL();
    }

    /**
     * @param String $elasticsearch - Elasticsearch URL
     */
    public function setElasticsearch($elasticsearch)
    {
        $this->curl->setBaseURL($elasticsearch);
    }



}