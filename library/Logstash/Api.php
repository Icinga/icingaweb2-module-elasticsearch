<?php

namespace Icinga\Module\Logstash;

use Icinga\Exception\ProgrammingError;
use Exception;
use Icinga\Data\DataArray\ArrayDatasource;

class Api
{
    /**
     * CURL handle
     *
     * @var resource
     */
    protected $curl;

    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function sampleSearch($hostname, $program, $message, $min_severity)
    {
        $filters = array(
            (object) array(
                'term' => (object) array(
                    'type' => 'syslog',
                ),
            ),
            (object) array(
                'range' => (object) array(
                    '@timestamp' => (object) array(
                        'from' => 'now-48h',
                        'to'   => 'now'
                    )
                )
            ),
            (object) array(
                'range' => (object) array(
                    'syslog_severity_code' => (object) array(
                        'lte' => $min_severity, // 4 is warn
                        // 'gte' => 7,
                    )
                )
            ),
        );
        
        if ($hostname) {
            foreach (preg_split('/-/', preg_replace('/\..*$/', '', $hostname)) as $host) {
                $filters[] = (object) array(
                    'term' => (object) array(
                        'hostname' => $host,// . '$',
                    )
                );
            }
        }
        if ($message) {
            foreach (preg_split('/[-\s]/', preg_replace('/\..*$/', '', $message)) as $msg) {
                 $filters[] = (object) array(
                    'term' => (object) array(
                        'message' => strtolower($msg),// . '$',
                    )
                );
            }
        }
        if ($program) {
            $filters[] = (object) array(
                'term' => (object) array(
                    'program' => $program,// . '$',
                )
            );
        }
        $search = (object) array(
            'fields' => array('_source'),
            'query'  => (object) array(
                'filtered' => (object) array(
                    'filter' => (object) array(
                        'bool' => (object) array(
                            'must' => $filters,
                            'must_not' => array(
                                (object) array(
                                    'term' => (object) array(
                                        'program' => 'suhosin'
                                    )
                                ),
                                /*
                                (object) array(
                                    'term' => (object) array(
                                        'program' => 'sudo'
                                    )
                                ),*/
                            )
                        )
                    ),
                )
            ),
            'sort' => array(
                (object) array( 
                    '@timestamp' => (object) array(
                        'order' => 'desc'
                    )
                )
            ),
            'size' => 50
        );

        return $this->post(
            '/_search',
            $search
        );
    }


    protected function get($url)
    {
        return $this->fetchUrl($url);
    }

    protected function post($url, $data)
    {
        return json_decode($this->fetchUrl($url, 'POST', $data));
    }

    protected function put($url, $data)
    {
        return $this->fetchUrl($url, 'PUT', $data);
    }

    protected function delete($url)
    {
        return $this->fetchUrl($url, 'DELETE');
    }

    // TODO: This will be replaced by Icinga\Protocol\Http
    protected function fetchUrl($url, $method = 'GET', $data = null)
    {
        $ch = $this->curl();
        $opts = array(
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS    => $data === null ? null : json_encode($data) . "\n"
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
            throw new Exception(
                sprintf(
                    'Elasticsearch API call failed with exit code %d: %s',
                    $status,
                    $result
                )
            );
        }
        if ($method === 'GET') {
            return json_decode($result);
        } else {
            return $result;
        }
    }

    protected function curl()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
            $opts = array(
                CURLOPT_RETURNTRANSFER => true,
                
                CURLOPT_HTTPHEADER     => array(
                    'Accept: application/json',
                    'Content-type: application/json' //;charset=utf-8
                ),

                // Just for debugging reasons right now:
                CURLOPT_HEADER         => true,
                CURLINFO_HEADER_OUT    => true,
            );
            curl_setopt_array($this->curl, $opts);
        }
        return $this->curl;
    }

    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }
}
