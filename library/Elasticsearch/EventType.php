<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Application\Logger;
use Icinga\Data\Filter\Filter;

use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Elasticsearch\Filter\FilterExpressions;
use Icinga\Module\Elasticsearch\Repository\EventTypeRepository;
use Icinga\Repository\RepositoryQuery;

// TODO: dependency!
use Icinga\Module\Monitoring\Object\Host;

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

    protected $hostmapFilter;

    /*
    protected $hostmapElasticsearch;

    protected $hostmapIcinga;
    */
    
    public function __construct($name)
    {
        $this->$name = $name;
    }

    /**
     * Load an EventType by its name
     *
     * @param   $name  string  event type name
     *
     * @return  EventType
     */
    public static function loadByName($name)
    {
        $repo = new EventTypeRepository();
        $row = $repo->select()->where('name', $name)->fetchRow();

        $type = new EventType($row->name);
        $type
            ->setLabel($row->label)
            ->setDescription($row->description)
            ->setFilter($row->filter)
            ->setFields($row->fields)
            ->setHostmapFilter($row->hostmap_filter);

        return $type;
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

    /**
     * Return a pre-filtered Query for events the host for this EventType
     *
     * @param  Host  $host  Icinga host object
     *
     * @return RepositoryQuery
     *
     * @throws ProgrammingError  When filter has not been set
     */
    public function getEventQueryForHost(Host $host)
    {
        $query = $this->getEventQuery();

        if (($filter = $this->getHostmapFilter()) === null) {
            throw new ProgrammingError('hostmap filter has not been set!');
        }

        $filterHandler = new FilterExpressions($filter);
        $filterHandler->resolve($host);
        $query->addFilter($filter);

        Logger::debug('Applying host filter: %s', $filter->toQueryString());

        return $query;
    }

    /**
     * Return the current Hostmap Filter
     *
     * @return Filter|null
     */
    public function getHostmapFilter()
    {
        return $this->hostmapFilter;
    }

    /**
     * Set the Hostmap Filter with a Filter or query string
     *
     * @param  string|Filter  $filter  The Filter or query string to set as Hostmap Filter
     *
     * @return $this
     *
     * @throws ProgrammingError        When the input is not a string or Filter
     */
    public function setHostmapFilter($filter)
    {
        if ($filter !== null) {
            if (is_string($filter)) {
                $this->hostmapFilter = Filter::fromQueryString($filter);
            }
            elseif ($filter instanceof Filter) {
                $this->hostmapFilter = $filter;
            }
            else {
                throw new ProgrammingError('hostmap filter must be a Filter or query string');
            }
        }
        return $this;
    }

}