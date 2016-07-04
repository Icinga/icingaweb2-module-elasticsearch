<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Filter;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;

use Icinga\Exception\IcingaException;
use Icinga\Exception\InvalidPropertyException;

use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Class FilterExpressions to replace expression in FilterExpression
 */
class FilterExpressions
{
    protected $filter;
    protected $pattern;

    const PATTERN_EXPRESSION = '#\${([^}]+|\\\})}#';

    const REGEX_ATTRIBUTE = '[0-9a-zA-z_\-]+';
    const REGEX_SEARCH    = '[^/]+|\/';
    const REGEX_REPLACE   = '[^/]*|\/';
    const REGEX_OPTIONS   = '[a-z]+';

    const PATTERN = '#^(%s)(?:/(%s)/(%s)(?:/%s)?)?$#';

    /**
     * FilterExpressions constructor
     *
     * @param  Filter  $filter  Work on this filter
     */
    public function __construct(Filter $filter)
    {
        $this->pattern = sprintf(
            static::PATTERN,
            static::REGEX_ATTRIBUTE,
            static::REGEX_SEARCH,
            static::REGEX_REPLACE,
            static::REGEX_OPTIONS
        );

        $this->filter = $filter;
    }

    /**
     * Locate and resolve expressions in the Filter with data from MonitoredObject
     *
     * @param  MonitoredObject  $object  Host or Service to get attributes from
     */
    public function resolve(MonitoredObject $object)
    {
        $this->findWalk($this->filter, $object);
    }

    /**
     * Walking recursively through the FilterChain
     *
     * @param  Filter           $filter  Current Filter component
     * @param  MonitoredObject  $object  Host or Service to get attributes from
     *
     * @throws IcingaException  When Filter is not supported
     * @throws IcingaException  When attribute could not be found
     */
    protected function findWalk(Filter $filter, MonitoredObject $object)
    {
        if ($filter->isExpression()) {
            /** @var FilterExpression $filter */
            $filterExpression = $filter->getExpression();
            if ($expressions = $this->getExpressions($filterExpression)) {
                // found expression(s) - try to evaluate
                foreach ($expressions as $expression) {
                    $components = $this->parseExpression($expression[1]);

                    $attr = $components[1];

                    // get value
                    try {
                        $value = $object->{$attr};
                    } catch (InvalidPropertyException $e) {
                        throw new IcingaException(
                            'MonitoredObject %s does not have attribute %s',
                            get_class($object), $attr
                        );
                    }

                    // replacement
                    if (count($components) > 2) {
                        $search = $components[2];
                        $search = preg_replace('/~/', '\~', $search);
                        $search = sprintf('~%s~', $search);
                        $replacement = $components[3] ?: '';

                        // TODO: support options
                        $value = preg_replace($search, $replacement, $value);
                    }

                    // replace expression with the new value
                    $filterExpression = str_replace($expression[0], $value, $filterExpression);
                    $filter->setExpression($filterExpression);
                }
            }
        }
        elseif ($filter->isChain()) {
            /** @var FilterChain $filter */
            foreach ($filter->filters() as $subFilter) {
                $this->findWalk($subFilter, $object);
            }
        }
        else {
            throw new IcingaException('unsupported Filter component: %s', var_export($filter, true));
        }
    }

    /**
     * Helper to look for expressions in string
     *
     * @param  string  $string  A string to look for expressions
     *
     * @return array|bool       Array with found expression matches, or false if nothing has been found
     */
    public function getExpressions($string)
    {
        if (preg_match_all(static::PATTERN_EXPRESSION, $string, $pattern, PREG_SET_ORDER)) {
            return $pattern;
        }
        else {
            return false;
        }
    }

    /**
     * Helper to parse an expression
     *
     * @param  string  $expression  The expression, the text between ${ and }
     *
     * @return array                The Array of matches
     *
     * @throws IcingaException      When the expression is invalid
     */
    public function parseExpression($expression)
    {
        if (preg_match($this->pattern, $expression, $components)) {
            return $components;
        }
        else {
            throw new IcingaException('invalid expression: %s', $expression);
        }
    }
}