<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;
use Icinga\Exception\IcingaException;
use Icinga\Web\UrlParams;

class RestApiRequest
{
    /**
     * This request's headers
     *
     * @var array
     */
    protected $headers;

    /**
     * This request's method
     *
     * @var string
     */
    protected $method;

    /**
     * This request's path
     *
     * @var string
     */
    protected $path;

    /**
     * This request's parameters
     *
     * @var UrlParams
     */
    protected $params;

    /**
     * This request's payload
     *
     * @var string
     */
    protected $payload;

    /**
     * The content type of this request's payload
     *
     * @var string
     */
    protected $contentType;

    /**
     * Set this request's headers
     *
     * @param   array   $headers
     *
     * @return  $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Return this request's headers
     *
     * @return  array
     */
    public function getHeaders()
    {
        if ($this->headers === null) {
            $this->headers = array();
        }

        if ($this->payload) {
            $this->headers[] = sprintf('Content-Length: %u', strlen($this->payload));
            if ($this->contentType) {
                $this->headers[] = sprintf('Content-Type: ' . $this->contentType);
            }
        }

        return $this->headers;
    }

    /**
     * Set this request's method
     *
     * @param   string  $method
     *
     * @return  $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Return this request's method
     *
     * @return  string
     */
    public function getMethod()
    {
        if ($this->method === null) {
            throw new LogicException('It is required to explicitly set a method');
        }

        return $this->method;
    }

    /**
     * Set this request's path
     *
     * @param   string  $path
     *
     * @return  $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Return this request's path
     *
     * @return  string
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = '';
        }

        return $this->path;
    }

    /**
     * Set this requests's parameters
     *
     * @param   UrlParams   $params
     *
     * @return  $this
     */
    public function setParams(UrlParams $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Return this request's parameters
     *
     * @return  UrlParams
     */
    public function getParams()
    {
        if ($this->params === null) {
            $this->params = new UrlParams();
        }

        return $this->params;
    }

    /**
     * Set this request's payload
     *
     * @param   string  $data
     * @param   string  $contentType
     *
     * @return  $this
     */
    public function setPayload($data, $contentType = null)
    {
        $this->payload = $data;
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * Return this request's payload
     *
     * @return  string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Return the given data encoded as JSON
     *
     * @param   mixed   $data
     *
     * @return  string
     *
     * @throws  IcingaException     In case the encoding has failed
     */
    protected function jsonEncode($data)
    {
        $data = json_encode($data);
        if ($data !== false) {
            return $data;
        }

        $errorNo = json_last_error();
        if ($errorNo === JSON_ERROR_CTRL_CHAR) {
            throw new IcingaException('Failed to encode JSON. Control character found.');
        } elseif ($errorNo === JSON_ERROR_UTF8) {
            throw new IcingaException('Failed to encode JSON. Input is not encoded with UTF-8.');
        } else {
            throw new IcingaException('Failed to encode JSON. Unknown error %u occurred.', $errorNo);
        }
    }
}
