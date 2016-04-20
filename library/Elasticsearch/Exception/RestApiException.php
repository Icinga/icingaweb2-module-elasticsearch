<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Exception;

use Icinga\Exception\IcingaException;

class RestApiException extends IcingaException
{
    /**
     * The curl error code
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Set the curl error code
     *
     * @param   int     $errorCode
     *
     * @return  $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = (int) $errorCode;
        return $this;
    }

    /**
     * Return the curl error code
     *
     * @return  int
     */
    public function getErrorCode()
    {
        return $this->code;
    }
}
