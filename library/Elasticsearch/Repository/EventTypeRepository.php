<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Repository;

use Exception;
use Icinga\Application\Config;
use Icinga\Module\Elasticsearch\EventType;

// TODO: remove this uses when #12065 gets released https://dev.icinga.org/issues/12065
use Icinga\Data\Filter\Filter;
use Icinga\Exception\StatementException;
use Icinga\Repository\IniRepository;

class EventTypeRepository extends IniRepository
{
    /**
     * Create a new EventTypeRepository object
     *
     * Data source is configured automatically.
     */
    public function __construct()
    {
        $config = Config::module('elasticsearch', 'event-types');
        $config->getConfigObject()->setKeyColumn('name');
        parent::__construct($config);
    }

    /**
     * List of all properties
     *
     * @return  array  column list
     */
    protected function initializeQueryColumns()
    {
        return array(
            'event-type' => array(
                'name',
                'label',
                'description',
                'filter',
                'fields',
                'hostmap_enabled',
                'hostmap_elasticsearch_expression',
                'hostmap_icinga_expression',
            )
        );
    }

    /**
     * Shorthand to fetchRow by name of event type
     *
     * @param   $name  string  event type name
     *
     * @return  EventType
     */
    public static function load($name)
    {
        $me = new static();
        $row = $me->select()->where('name', $name)->fetchRow();
        
        $type = new EventType($row->name);
        $type
            ->setLabel($row->label)
            ->setDescription($row->description)
            ->setFilter($row->filter)
            ->setFields($row->fields);
        
        return $type;
    }

    /**
     * Update the target with the given data and optionally limit the affected entries by using a filter
     *
     * @param   string  $target
     * @param   array   $data
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     *
     * @todo remove this code when #12065 gets released https://dev.icinga.org/issues/12065
     */
    public function update($target, array $data, Filter $filter = null)
    {
        $newData = $this->requireStatementColumns($target, $data);
        $keyColumn = $this->ds->getConfigObject()->getKeyColumn();
        if ($filter === null && isset($newData[$keyColumn])) {
            throw new StatementException(
                t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                $keyColumn
            );
        }

        if ($filter !== null) {
            $filter = $this->requireFilter($target, $filter);
        }

        $newSection = null;

        $query = $this->ds->select();
        $query->addFilter($filter);

        foreach ($query as $section => $config) {
            if ($newSection !== null) {
                throw new StatementException(
                    t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                    $keyColumn
                );
            }

            foreach ($newData as $column => $value) {
                if ($column === $keyColumn) {
                    $newSection = $value;
                } else {
                    $config->$column = $value;
                }
            }

            if ($newSection) {
                if ($this->ds->hasSection($newSection)) {
                    throw new StatementException(t('Cannot update. Section "%s" does already exist'), $newSection);
                }

                $this->ds->removeSection($section)->setSection($newSection, $config);
            } else {
                $this->ds->setSection($section, $config);
            }
        }

        try {
            $this->ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to update. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Delete entries in the given target, optionally limiting the affected entries by using a filter
     *
     * @param   string  $target
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     *
     * @todo remove this code when #12065 gets released https://dev.icinga.org/issues/12065
     */
    public function delete($target, Filter $filter = null)
    {
        if ($filter !== null) {
            $filter = $this->requireFilter($target, $filter);
        }

        $query = $this->ds->select();
        $query->addFilter($filter);

        foreach ($query as $section => $config) {
            $this->ds->removeSection($section);
        }

        try {
            $this->ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to delete. An error occurred: %s'), $e->getMessage());
        }
    }

}
