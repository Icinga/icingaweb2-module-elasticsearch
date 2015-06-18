<?php

namespace Icinga\Module\Logstash;

use Exception;

class Curl {
    /**
     * CURL handle
     *
     * @var resource
     */
    protected $curl = null;

    protected $baseURL = null;

    protected $json_header = array(
        'Accept: application/json',
        'Content-type: application/json' //;charset=utf-8
    );

    public function __construct()
    {
        if (!function_exists("curl_init"))
            throw new Exception("PHP module curl seems not to be installed or enabled!");

        $this->curl = curl_init();
        $opts = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            //CURLINFO_HEADER_OUT    => true,
        );
        curl_setopt_array($this->curl, $opts);
    }

    public function getBaseURL() {
        return $this->baseURL;
    }

    public function setBaseURL($url) {
        $this->baseURL = $url;
        return $this;
    }

    public function get($url)
    {
        return $this->fetchUrl($url);
    }

    public function get_json($url) {
        return json_decode($this->get($url), undef, null, $this->json_header);
    }

    public function post($url, $data)
    {
        return $this->fetchUrl($url, 'POST', $data);
    }

    public function post_json($url, $data)
    {
        return json_decode($this->fetchUrl($url, 'POST', json_encode($data), $this->json_header));
    }

    /* TODO: implement
    protected function put($url, $data)
    {
        return $this->fetchUrl($url, 'PUT', $data);
    }

    protected function delete($url)
    {
        return $this->fetchUrl($url, 'DELETE');
    }
    */

    // TODO: This will be replaced by Icinga\Protocol\Http
    protected function fetchUrl($url, $method = 'GET', $data = null, $headers = array())
    {
        $ch = $this->curl;
        if ($this->baseURL)
            $url = $this->baseURL . $url;

        $opts = array(
            CURLOPT_URL           => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS    => $data === null ? null : $data, // TODO: closing newline?
            CURLOPT_HTTPHEADER    => $headers,
        );
        curl_setopt_array($ch, $opts);

        $result = curl_exec($ch);
        if ($result === false) {
            throw new Exception('Elasticsearch API call failed: ' . curl_error($ch));
        }

        list($header, $result) = preg_split('~\r\n\r\n~', $result, 2);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status === 404) {
            return false;
        }

        if ($status !== 200) {
            $short_result = $result;
            if (strlen($short_result) > 50)
                $short_result = substr($short_result, 0, 47)."...";

            throw new Exception(
                sprintf(
                    'Elasticsearch API call failed with exit code %d: %s',
                    $status,
                    $short_result
                )
            );
        }
        return $result;
    }

    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }
}