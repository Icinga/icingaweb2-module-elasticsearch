<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Web\Widget;

use Icinga\Application\Icinga;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Module\Elasticsearch\Forms\Widget\FieldSelectorForm;

/**
 * FieldSelector widget
 */
class FieldSelector extends AbstractWidget
{
    /**
     * The array of available fields to choose from
     *
     * @var array
     */
    protected $fieldsAvailable;

    /**
     * The array of fields from the URL
     *
     * @var array
     */
    protected $fields;

    /**
     * Returns a ordered list of fields to show in the table
     *
     * @return array
     */
    public function getFields()
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $fields = Icinga::app()->getRequest()->getUrl()->getParam('fields');
        $fields = preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY);

        return $this->fields = $fields;
    }

    /**
     * Render this FieldSelector as HTML
     *
     * @return  string
     */
    public function render()
    {
        $control = new FieldSelectorForm();

        // TODO: set available fields to form
        //$formFields = array_combine($this->fieldsAvailable, $this->fieldsAvailable);
        //array_unshift($formFields, array('' => t('Please choose a field')));
        
        $control->handleRequest();

        return (string) $control;
    }

    /**
     * @return array
     */
    public function getFieldsAvailable()
    {
        return $this->fieldsAvailable;
    }

    /**
     * @param array $fieldsAvailable
     * @return $this
     */
    public function setFieldsAvailable($fieldsAvailable)
    {
        $this->fieldsAvailable = $fieldsAvailable;
        return $this;
    }
}
