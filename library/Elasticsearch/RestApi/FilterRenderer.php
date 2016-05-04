<?php
/* Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use Icinga\Application\Logger;
use Icinga\Data\Filter\FilterMatch;
use Icinga\Data\Filter\FilterMatchNot;
use Icinga\Exception\ProgrammingError;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\Filter\FilterNot;
use Icinga\Data\Filter\FilterOr;

/**
 * Class FilterRenderer
 *
 * Provides Rendering of Icingaweb2 filters to an Elasticsearch query.
 *
 * Obviously this class is limited to the feature set of Icinga\Data\Filter\Filter.
 *
 * @package Icinga\Module\Elasticsearch
 */
class FilterRenderer {

    /** @var  Filter */
    protected $filter;

    /** @var  array */
    protected $query;

    /**
     * FilterRenderer constructor.
     *
     * @param Filter|null $filter
     */
    public function __construct(Filter $filter = null)
    {
        if ($filter !== null) {
            $this->setFilter($filter);
        }
    }

    /**
     * Set the filter and render it internally.
     *
     * @param  Filter $filter
     *
     * @return $this
     *
     * @throws ProgrammingError
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        $this->query = $this->renderFilter($this->filter);
        Logger::debug('Rendered elasticsearch filter: %s', json_encode($this->query, JSON_PRETTY_PRINT));
        return $this;
    }

    /**
     * Returns the rendered filter as an Array for the REST API.
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Renders the Elasticsearch query as a recursive function, walking through the FilterChain.
     *
     * @param Filter  $filter    Current object
     * @param int     $level     Current depth in integer
     *
     * @return array|null|string
     *
     * @throws ProgrammingError
     */
    protected function renderFilter(Filter $filter, $level = 0)
    {
        $container = array();
        if ($filter->isChain()) {
            if ($filter instanceof FilterAnd) {
                $section = 'must';
            } elseif ($filter instanceof FilterOr) {
                $section = 'should';
            } elseif ($filter instanceof FilterNot) {
                $section = 'must_not';
            } else {
                throw new ProgrammingError('Cannot render filter chain type: %s', get_class($filter));
            }

            if (! $filter->isEmpty()) {
                /** @var Filter $filterPart */
                foreach ($filter->filters() as $filterPart) {
                    $part = $this->renderFilter($filterPart, $level + 1);
                    if ($part) {
                        if ($filter instanceof FilterNot) {
                            // add in a new bool to flip expression
                            if ($filterPart instanceof FilterMatchNot) {
                                $container[$section][] = array(
                                    'bool' => array(
                                        'must_not' => $part,
                                    ),
                                );
                                continue;
                            }
                        } elseif ($filter instanceof FilterAnd) {
                            // add match not to must_not instead of must
                            if ($filterPart instanceof FilterMatchNot) {
                                $container['must_not'][] = $part;
                                continue;
                            }
                            // merge in must_not
                            elseif ($filterPart instanceof FilterNot) {
                                $container['must_not'] = $part['bool']['must_not'];
                                continue;
                            }
                        }
                        $container[$section][] = $part;
                    }
                }
                // return the bool of the chain
                return array('bool' => $container);
            } else {
                // return match_all
                return array(
                    'match_all' => (object) array(),
                );
            }
        } else {
            // return the simple part
            return $this->renderFilterExpression($filter);
        }
    }

    /**
     * Render and return the given filter expression.
     *
     * This handles non-chain parts of the Filter.
     *
     * @param   Filter   $filter
     * @return  string
     * @throws  ProgrammingError
     */
    protected function renderFilterExpression(Filter $filter)
    {
        /** @var FilterMatch $filter (just for resolving) */
        $column = $filter->getColumn();
        $sign = $filter->getSign();
        $value = $filter->getExpression();

        // array or lists
        if (is_array($value)) {
            if ($sign === '=' || $sign === '!=') {
                return array(
                    'query_string' => array(
                        'default_field' => $column,
                        'query'         => '"' . join('" "', $value) . '"'
                    )
                );
            }

            throw new ProgrammingError(
                'Unable to render array expressions with operators other than equal or not equal'
            );
        }
        // with wildcards
        elseif (strpos($value, '*') !== false) {
            if ($value === '*') {
                // (sign =) We'll ignore such filters as it prevents index usage and because "*" means anything, anything means
                // all whereas all means that whether we use a filter to match anything or no filter at all makes no
                // difference, except for performance reasons...

                // (sign !=) We'll ignore such filters as it prevents index usage and because "*" means nothing, so whether we're
                // using a real column with a valid comparison here or just an expression which cannot be evaluated to
                // true makes no difference, except for performance reasons...
                return null;
            }

            if ($sign === '=' || $sign === '!=') {
                return array(
                    'query_string' => array(
                        'default_field'     => $column,
                        'query'             => $value,
                        'analyze_wildcard'  => true
                    )
                );

            }

            throw new ProgrammingError(
                'Unable to render expressions wildcards other than equal or not equal'
            );
        }
        // any other value
        else {
            // simple comparison via match
            if ($sign === '=' || $sign === '!=') {
                return array(
                    'match' => array(
                        $column => $value,
                    ),
                );
            } elseif (preg_match('/^[<>]=?$/', $sign)) {
                $param_map = array(
                    '>' => 'gt',
                    '<' => 'lt',
                    '>=' => 'gte',
                    '<=' => 'lte',
                );
                return array(
                    'range' => array(
                        $column => array(
                            $param_map[$sign] => $value,
                        ),
                    ),
                );
            }

            throw new ProgrammingError(
                'Unable to render string expressions with operators other than equality and range'
            );
        }
    }

}
