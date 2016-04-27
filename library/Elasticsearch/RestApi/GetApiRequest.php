<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

use LogicException;

class GetApiRequest extends DocumentApiRequest
{
    /**
     * {@inheritdoc}
     */
    protected $method = 'GET';

    /**
     * Whether to only fetch the source
     *
     * @var bool
     */
    protected $sourceOnly;

    /**
     * Set whether to only fetch the source
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setSourceOnly($state = true)
    {
        $this->sourceOnly = (bool) $state;
        return $this;
    }

    /**
     * Return whether to only fetch the source
     *
     * @return  bool
     */
    public function getSourceOnly()
    {
        return $this->sourceOnly ?: false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPath()
    {
        if ($this->id === null) {
            throw new LogicException('GetApiRequest is missing a document id');
        }

        $path = sprintf('/%s/%s/%s', $this->index, $this->documentType, $this->id);
        if ($this->getSourceOnly()) {
            $path .= '/_source';
        }

        return $path;
    }
}
