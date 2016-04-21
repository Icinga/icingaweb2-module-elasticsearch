<?php

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Elasticsearch\Helpers\QueryString;

/**
 * @deprecated reimplement
 * @todo reimplement!!
 */
trait IcingaStatus
{
    protected $icinga_status_fields = array();

    protected $icinga_warning_query = null;
    protected $icinga_critical_query = null;

    protected $icinga_warning_count = 0;
    protected $icinga_critical_count = 0;

    /**
     * @param String $query_string Elasticsearch compatible query_string
     * @return $this
     */
    public function setIcingaWarningQuery($query_string) {
        if ($this->icinga_warning_query === null)
            $this->icinga_warning_query = new QueryString();

        $this->icinga_warning_query->parse($query_string);
        return $this;
    }

    /**
     * @param String $query_string Elasticsearch compatible query_string
     * @return $this
     */
    public function setIcingaCriticalQuery($query_string) {
        if ($this->icinga_critical_query === null)
            $this->icinga_critical_query = new QueryString();

        $this->icinga_critical_query->parse($query_string);
        return $this;
    }

    /**
     * @return int
     */
    public function getIcingaWarningCount()
    {
        return $this->icinga_warning_count;
    }

    /**
     * @return int
     */
    public function getIcingaCriticalCount()
    {
        return $this->icinga_critical_count;
    }

    /**
     * @return QueryString
     */
    public function getIcingaWarningQuery()
    {
        return $this->icinga_warning_query;
    }

    /**
     * @return QueryString
     */
    public function getIcingaCriticalQuery()
    {
        return $this->icinga_critical_query;
    }

    /**
     * @param Array $document
     */
    protected function evalIcingaStatus(Array &$document)
    {
        assert(is_array($document), '$document must be an Array!');

        $document['icinga_status'] = 0;
        if ($this->icinga_warning_query !== null and $this->icinga_warning_query->match($document) === true) {
            $document['icinga_status'] = 1;
            $this->icinga_warning_count++;
        }
        if ($this->icinga_critical_query !== null and $this->icinga_critical_query->match($document) === true) {
            $document['icinga_status'] = 2;
            $this->icinga_critical_count++;
        }
    }

}