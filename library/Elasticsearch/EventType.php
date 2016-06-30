<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Data\Filter\Filter;

use Icinga\Exception\IcingaException;
use Icinga\Repository\RepositoryQuery;

/**
 * EventType to help handling with types
 *
 * @package Icinga\Module\Elasticsearch
 */
class EventType
{
    /**
     * ID name
     * @var string
     */
    protected $name;

    /**
     * User readable name
     * @var string
     */
    protected $label;

    /**
     * Helpful description
     *
     * @var string
     */
    protected $description;

    /**
     * A filter to restrict
     *
     * @var Filter
     */
    protected $filter;

    /**
     * List of fields to display
     *
     * @var array
     */
    protected $fields;

    
    public function __construct($name)
    {
        $this->$name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label ?: $this->name;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Filter
     * @throws IcingaException
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            throw new IcingaException('filter has not been set!');
        }
        return $this->filter;
    }

    /**
     * @param Filter $filter
     * @return $this
     * @throws IcingaException
     */
    public function setFilter($filter)
    {
        if (is_string($filter)) {
            $this->filter = Filter::fromQueryString($filter);
            return $this;
        }
        else {
            throw new IcingaException('EventType filter can not be empty!');
        }
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param   array|string     $fields  A list of fields, as an Array or comma separated
     *
     * @return  $this
     *
     * @throws  IcingaException  On invalid input
     */
    public function setFields($fields)
    {
        if (is_array($fields)) {
            $this->fields = $fields;
        }
        elseif (is_string($fields)) {
            $fields = trim($fields);
            $this->fields = preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }
        else {
            throw new IcingaException('can only set fields from an Array or comma seperated string!');
        }
        return $this;
    }

    /**
     * Return a pre-filtered Query for EventBackend
     *
     * @return  RepositoryQuery
     */
    public function getEventQuery()
    {
        $repository = EventBackend::fromConfig();

        $query = $repository->select();
        $query->addFilter($this->getFilter());

        return $query;
    }
}