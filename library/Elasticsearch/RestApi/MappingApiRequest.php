<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class MappingApiRequest extends IndicesApiRequest
{
    /**
     * A list of types to work with.
     *
     * @var array
     */
    protected $types;

    /**
     * Creates a new MappingApiRequest.
     * 
     * @param  string  $index  The index name or pattern
     * @param  array   $types  A list of types to get mappings for
     */
    public function __construct($index, $types = array())
    {
        parent::__construct($index);
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        if (! empty($this->types)) {
            $types = array();
            foreach ($this->types as $type) {
                if (preg_match('#,#', $type)) {
                    throw new LogicException('a type must not contain a comma!');
                }
                $types = urlencode(trim($type));
            }
            return sprintf('/%s/_mapping/%s', $this->index, join(',', $types));
        }
        return sprintf('/%s/_mapping', $this->index);
    }
}
