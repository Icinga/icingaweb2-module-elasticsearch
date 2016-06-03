<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms\Widget;

use Icinga\Web\Form;

/**
 * FieldSelector control form
 */
class FieldSelectorForm extends Form
{
    /**
     * CSS class for the limiter control
     *
     * @var string
     */
    const CSS_CLASS = 'fieldSelector';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', static::CSS_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl()
    {
        return $this->getRequest()->getUrl()
            ->setParam('fields', $this->getElement('fields')->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'fields',
            array(
                //'autosubmit'    => true,
                'placeholder'   => mt('elasticsearch', 'Fields...'),
                'value'         => $this->getRequest()->getUrl()->getParam('fields'),
                'decorators'   => array(
                    array('ViewHelper'),
                    array('Label')
                )
            )
        );
    }

    /**
     * Limiter control is always successful
     *
     * @return bool
     */
    public function onSuccess()
    {
        return true;
    }
}
