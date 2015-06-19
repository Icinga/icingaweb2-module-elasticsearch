<?php

namespace Icinga\Module\Logstash;

use Exception;

trait IcingaStatus
{
    protected $icinga_status_fields = array();
    protected $icinga_patterns = array();

    public function clearIcingaPatterns()
    {
        $this->icinga_patterns = array();
    }

    public function addIcingaPattern($for, $field, $pattern)
    {
        assert($for == 'warning' or $for == 'critical', '$for must be warning or critical');

        if (!in_array($field, $this->icinga_status_fields))
            $this->icinga_status_fields[] = $field;

        $this->icinga_patterns[$for][] = array(
            'field'   => $field,
            'pattern' => $pattern
        );
    }

    /**
     * @return array
     */
    public function getIcingaPatterns()
    {
        return $this->icinga_patterns;
    }

    protected function IcingaStatusMatcher($pattern, $value)
    {
        if (false) // TODO: build other patterns
            return preg_match($pattern, $value) === 1;
        else
            return strpos($value, $pattern) !== false;
    }

    protected function evalIcingaStatus(&$document)
    {
        if (!is_array($document))
            throw new Exception('$document must be an Array');

        $warning = 0;
        $critical = 0;
        foreach ($this->icinga_patterns as $status => $list) {
            foreach ($list as $el) {
                $field = $el['field'];
                $pattern = $el['pattern'];
                if (array_key_exists($field, $document))
                    if ($this->IcingaStatusMatcher($pattern, $document[$field]))
                        ${$status}++;
            }
        }

        $document['icinga_status'] =
            ($critical > 0) ? 2 : ($warning > 0 ? 1 : 0);
        ;
    }

    public function parseIcingaQueryString($for, $query_string)
    {

        $matches = array();
        if (preg_match_all("/(\w+):(\"[^\"]*?\"|\([^\)]*?\)|\S+)/", $query_string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $field = $match[1];
                $pattern = $match[2];
                $this->addIcingaPattern($for, $field, $pattern);
            }
        }

    }

}