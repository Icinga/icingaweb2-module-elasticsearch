<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use ArrayAccess;
use LogicException;
use Icinga\Application\Logger;
use Icinga\Exception\NotImplementedError;

class SearchHit implements ArrayAccess
{
    /**
     * This hit's data
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new SearchHit
     *
     * @param   array   $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Create and return a row for this hit
     *
     * @param   array   $columns
     *
     * @return  object
     */
    public function createRow(array $columns)
    {
        if (empty($columns)) {
            return (object) $this->data;
        }

        $row = array();
        foreach ($this->treeify($columns) as $alias => $field) {
            if (isset($field['object_path'])) {
                $row[$alias] = $this->extractObject(
                    $field['object_path'],
                    $field['fields'],
                    $this->data
                );
            } else {
                $row[$alias] = $this->extractScalar(
                    $field,
                    $this->data
                );
            }
        }

        return (object) $row;
    }

    /**
     * Create a row for this hit and unfold it to multiple rows using the given field
     *
     * @param   array   $columns
     * @param   string  $foldedColumn
     *
     * @return  array
     */
    public function createRows(array $columns, $foldedColumn)
    {
        if (strpos($foldedColumn, '.') !== false) {
            throw new NotImplementedError('Nested columns cannot be unfolded');
        }

        $row = $this->createRow($columns);
        if (empty($row->{$foldedColumn})) {
            return array();
        } elseif (! is_array($row->{$foldedColumn})) {
            return array($row);
        }

        $rows = array();
        foreach ($row->{$foldedColumn} as $value) {
            $newRow = clone $row;
            $newRow->{$foldedColumn} = $value;
            $rows[] = $newRow;
        }

        return $rows;
    }

    /**
     * Extract and return one or more scalar values from the given data
     *
     * @param   array   $fieldPath
     * @param   array   $data
     *
     * @return  mixed|array
     */
    protected function extractScalar(array $fieldPath, array $data)
    {
        if (! $this->isAssociative($data)) {
            $values = array();
            foreach ($data as $value) {
                if (is_array($value)) {
                    $fieldValue = $this->extractScalar($fieldPath, $value);
                    if (is_array($fieldValue)) {
                        $values = array_merge($values, $fieldValue);
                    } elseif ($fieldValue !== null) {
                        $values[] = $fieldValue;
                    }
                } else {
                    Logger::debug('Expected non-scalar value but got "%s" instead', $value);
                }
            }

            return $values;
        }

        $field = array_shift($fieldPath);
        if (isset($data[$field])) {
            if (empty($fieldPath)) {
                return $data[$field];
            } elseif (! is_array($data[$field])) {
                Logger::debug('Expected non-scalar value but got "%s" instead', $data[$field]);
            } else {
                return $this->extractScalar($fieldPath, $data[$field]);
            }
        }
    }

    /**
     * Extract and return one or more objects from the given data
     *
     * @param   array   $objectPath
     * @param   array   $fields
     * @param   array   $data
     *
     * @param   object|array
     */
    protected function extractObject(array $objectPath, array $fields, array $data)
    {
        if (! $this->isAssociative($data)) {
            $values = array();
            foreach ($data as $value) {
                if (is_array($value)) {
                    $objectValue = $this->extractObject($objectPath, $fields, $value);
                    if (is_array($objectValue)) {
                        $values = array_merge($values, $objectValue);
                    } elseif ($objectValue !== null) {
                        $values[] = $objectValue;
                    }
                } else {
                    Logger::debug('Expected non-scalar value but got "%s" instead', $value);
                }
            }

            return $values;
        }

        $object = array_shift($objectPath);
        if (isset($data[$object])) {
            if (! is_array($data[$object])) {
                Logger::debug('Expected non-scalar value but got "%s" instead', $data[$object]);
            } elseif (! empty($objectPath)) {
                return $this->extractObject($objectPath, $fields, $data[$object]);
            } elseif ($this->isAssociative($data[$object])) {
                $properties = array();
                foreach ($fields as $alias => $field) {
                    if (isset($field['object_path'])) {
                        $properties[$alias] = $this->extractObject(
                            $field['object_path'],
                            $field['fields'],
                            $data[$object]
                        );
                    } else {
                        $properties[$alias] = $this->extractScalar(
                            $field,
                            $data[$object]
                        );
                    }
                }

                return (object) $properties;
            } else {
                $objects = array();
                foreach ($data[$object] as $objectData) {
                    $properties = array();
                    foreach ($fields as $alias => $field) {
                        if (isset($field['object_path'])) {
                            $properties[$alias] = $this->extractObject(
                                $field['object_path'],
                                $field['fields'],
                                $objectData
                            );
                        } else {
                            $properties[$alias] = $this->extractScalar(
                                $field,
                                $objectData
                            );
                        }
                    }

                    $objects[] = (object) $properties;
                }

                return $objects;
            }
        }
    }

    /**
     * Turn the given flat column map into a tree
     *
     * @param   array   $columns
     * @param   array   $knownParents
     *
     * @return  array
     *
     * @todo    Make alias identifiers in $knownParents unique, may lead to strange effects otherwise in some cases
     */
    protected function treeify(array $columns, array &$knownParents = null)
    {
        $prependSource = false;
        if ($knownParents === null) {
            $knownParents = array();
            $prependSource = isset($this->data['_source']);
        }

        $tree = array();
        foreach ($columns as $alias => $field) {
            if (! is_string($alias)) {
                $alias = $field;
            }

            if (strpos($alias, '.') === false) {
                $tree[$alias] = explode('.', $field);
                if ($prependSource && !$this->isMetaField($field)) {
                    array_unshift($tree[$alias], '_source');
                }
            } elseif (strpos($field, '.') === false) {
                throw new LogicException('Desired nested objects must refer to nested objects in the search result');
            } else {
                list($aliasParent, $aliasChild) = explode('.', $alias, 2);
                list($fieldParent, $fieldChild) = explode('.', $field, 2);
                if (isset($knownParents[$aliasParent])) {
                    $knownParents[$aliasParent]['fields'] = array_merge(
                        $knownParents[$aliasParent]['fields'],
                        $this->treeify(array($aliasChild, $fieldChild), $knownParents)
                    );
                } else {
                    $tree[$aliasParent] = array(
                        'object_path'   => $prependSource && !$this->isMetaField($fieldParent)
                            ? array('_source', $fieldParent)
                            : array($fieldParent),
                        'fields'        => $this->treeify(
                            array($aliasChild => $fieldChild),
                            $knownParents
                        )
                    );

                    $knownParents[$aliasParent] =& $tree[$aliasParent];
                }
            }
        }

        return $tree;
    }

    /**
     * Return whether the given array is an associative one
     *
     * @param   array   $data
     *
     * @return  bool
     */
    protected function isAssociative(array $data)
    {
        reset($data);
        return is_string(key($data));
    }

    /**
     * Return whether the given field is a meta field
     *
     * @param   string  $field
     *
     * @return  bool
     */
    protected function isMetaField($field)
    {
        return substr($field, 0, 1) === '_';
    }
}
