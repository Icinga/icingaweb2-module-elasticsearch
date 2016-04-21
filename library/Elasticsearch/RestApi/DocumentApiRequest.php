<?php
/* Icinga Web 2 Elasticsearch Module | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\RestApi;

abstract class DocumentApiRequest extends RestApiRequest
{
    /**
     * The index where to store the document
     *
     * @var string
     */
    protected $index;

    /**
     * The type to store the document as
     *
     * @var string
     */
    protected $documentType;

    /**
     * The id of the document
     *
     * @var string
     */
    protected $id;

    /**
     * Create a new DocumentApiRequest
     *
     * @param   string  $index          The index where to store the document
     * @param   string  $documentType   The type to store the document as
     * @param   string  $id             The id of the document
     * @param   array   $data           The data of the document
     */
    public function __construct($index, $documentType, $id = null, array $data = null)
    {
        $this->id = $id;
        $this->index = $index;
        $this->documentType = $documentType;
        $this->setPayload($data, $data !== null ? 'application/json' : null);
    }

    /**
     * Create and return the path for this DocumentApiRequest
     *
     * @return  string
     */
    abstract protected function createPath();

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = $this->createPath();
        }

        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        $payload = parent::getPayload();
        if ($payload !== null) {
            $payload = $this->jsonEncode($payload);
        }

        return $payload;
    }
}
