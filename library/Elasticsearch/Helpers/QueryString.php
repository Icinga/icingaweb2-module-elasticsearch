<?php

namespace Icinga\Module\Elasticsearch\Helpers;

use Icinga\Exception\IcingaException as Exception;


class QueryString {

    protected $tree = array();
    protected $debug = array();
    protected $parsed = false;
    protected $matchedTree = array();

    protected $query_string;

    protected function debug($value, $op) {
        $this->debug[] = array(
            'value' => $value,
            'op' => $op
        );
    }

    public function getDebug() {
        return $this->debug;
    }

    protected function parse_parts($parts, $default_operator, $level=0) {

        $current = array();
        $in_block = array();
        $capture = array();
        $query = array();

        $max = count($parts);

        $this->debug(join("", $parts), 'parse parts');

        for ($i = 0; $i < $max; $i++) {
            $cur = $parts[$i];
            $next = ($i+1 < $max) ? $parts[$i+1] : null;

            // block end
            if (
                count($in_block) > 0
                and (
                    $cur === end($in_block)
                    or ($cur === ")" and end($in_block) === "(")
                )
            ) {
                if (count($in_block) === 1) {
                    $this->debug($cur, 'end block');
                    if ($current['type'] == 'sub') {
                        $current = array_merge($current, $this->parse_parts($capture, $default_operator, $level+1));
                    }
                    else
                        $current['value'] = join("", $capture);
                    $capture = array();
                }
                else {
                    $this->debug($cur, 'end sub-block');
                    $capture[] = $cur;
                }

                array_pop($in_block);
            }
            // block start
            elseif (in_array($cur, ["(",'"', '/']) === true) {
                if ($cur == "(")
                    $current['type'] = 'sub';
                elseif ($cur == '"')
                    $current['type'] = 'exact';
                elseif ($cur == '/')
                    $current['type'] = 'regexp';

                $this->debug($cur, 'enter block');
                array_push($in_block, $cur);
            }
            // in block
            elseif (count($in_block) > 0) {
                $this->debug($cur, 'capturing block');
                $capture[] = $cur;
            }
            // a field, next is :
            elseif ($next !== null and $next == ':') {
                $this->debug($cur, 'found field name');
                $current['field'] = $cur;
            }
            // jump over when field name is set
            elseif ($cur == ':') {
                $this->debug($cur, 'found field separator');
                if (! isset($current['field'])) {
                    throw new Exception("Illegal use of : in query_string!");
                }
            }
            // when empty
            elseif ($cur == ' ') {
                $this->debug($cur, 'found empty, saving current');
                if (count($current) > 0)
                    $query[] = $current;
                $current = array();
            }
            elseif (preg_match("/^(AND|OR|NOT)$/", $cur)) {
                // TODO
                throw new Exception('Operators are not yet implemented!');
            }
            else {
                $this->debug($cur, 'found value');
                if (preg_match('/(\?|\*)/', $cur) === 1) {
                    $reg = preg_quote($cur, '/');

                    $reg = str_replace('\?', '.', $reg);
                    $reg = str_replace('\*', '.*', $reg);

                    $current['type'] = 'regexp';
                    $current['value'] = $reg;
                    $current['o_value'] = $cur;
                }
                else
                    $current['value'] = $cur;
            }
            // TODO: sub when operator found!
            // TODO: negation
        }


        // add the last element, if any
        if (count($current) > 0)
            $query[] = $current;

        $this->debug('end', 'end of parts');

        return array(
            'operator' => $default_operator,
            'parts'    => $query
        );
    }

    public function parse($query_string, $default_operator = 'OR') {
        $tree = array();

        $this->query_string = trim($query_string);

        // split by parts
        $parts = preg_split("#([:\s\(\)\"/])#", $this->query_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $this->tree = $this->parse_parts($parts, $default_operator);
        $this->parsed = true;

        return $this;
    }

    /**
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    protected function compareBool($array, $op) {
        assert($op == 'AND' or $op == 'OR', '$op must be AND or OR!');

        $res = null;
        foreach($array as $a) {
            assert(is_bool($a), 'each array element must be bool!');

            if ($res === null)
                $res = $a;
            elseif ($op == 'AND')
                $res = ($res and $a);
            elseif ($op == 'OR')
                $res = ($res or $a);
        }
        return $res;
    }

    protected function compareValue($needle, $haystack, $mode='simple') {
        switch($mode) {
            case 'simple':
                return stristr($haystack, $needle) !== false;
            case 'regexp':
                return preg_match('/'.$needle.'/', $haystack) === 1;
            case 'exact':
                return strcmp($needle, $haystack) === 0;
            default:
                throw new Exception('mode %s not implemented!', $mode);
        }

    }
    protected function compareDocument(&$document, $val, $field=null, $mode='simple') {
        if ($field !== null) {
            if (isset($document[$field]))
                return $this->compareValue($val, $document[$field], $mode);
            else
                return false;
        }
        else {
            foreach ($document as $key => $value) {
                if ($this->compareValue($val, $value, $mode))
                    return true;
            }
            return false;
        }

    }


    public function match($document) {
        if (!$this->parsed)
            throw new Exception('You need to parse a query_string before using match!');

        $document = (array) $document;

        $tree = $this->tree;

        $state = $this->walkTree($document, $tree);

        $this->matchedTree = $tree;
        return $state;
    }

    protected function walkTree(&$document, &$tree, $cur=array(), $level=0) {
        if (array_key_exists('operator', $tree)) {
            $cur['operator'] = $tree['operator'];
        }
        if (array_key_exists('field', $tree)) {
            $cur['field'] = $tree['field'];
        }

        if (array_key_exists('type', $tree) and ($type = $tree['type']) !== 'sub') {
            return $tree['state'] = $this->compareDocument($document, $tree['value'],
                (isset($cur['field']) ? $cur['field'] : null),
                $type
            );
        }
        elseif (array_key_exists('parts', $tree)) {
            $bool = array();
            for ($i = 0; $i < count($tree['parts']); $i++) {
                $bool[] = $this->walkTree($document, $tree['parts'][$i], $cur, $level+1);
            }
            return $tree['state'] = $this->compareBool($bool, $cur['operator']);
        }
        elseif (array_key_exists('value', $tree)) {
            return $tree['state'] = $this->compareDocument($document, $tree['value'],
                (isset($cur['field']) ? $cur['field'] : null),
                'simple'
            );
        }
        else {
            throw new Exception("Unknown part of filter found: %s", var_export($tree, true));
        }
    }

    /**
     * @return array
     */
    public function getMatchedTree()
    {
        return $this->matchedTree;
    }

    /**
     * @return mixed
     */
    public function getQueryString()
    {
        return $this->query_string;
    }

}