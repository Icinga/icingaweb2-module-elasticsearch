<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;
use Icinga\Module\Elasticsearch\Eventtypes;
use Icinga\Module\Elasticsearch\Instances;

/**
 * Create, update and delete event types
 */
class EventtypeConfigForm extends RepositoryForm
{
    public function init()
    {
        $this->repository = new Eventtypes();
        $this->redirectUrl = 'elasticsearch/eventtypes';
    }

    /**
     * Set the identifier
     *
     * @param   string  $identifier
     *
     * @return  $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Set the mode of the form
     *
     * @param   int $mode
     *
     * @return  $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    protected function onUpdateSuccess()
    {
        if ($this->getElement('btn_remove')->isChecked()) {
            $this->setRedirectUrl("elasticsearch/eventtypes/delete?eventtype={$this->getIdentifier()}");
            $success = true;
        } else {
            $success = parent::onUpdateSuccess();
        }

        return $success;
    }

    protected function createBaseElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'description'   => $this->translate('Name of the Event type'),
                'label'         => $this->translate('Event Type Name'),
                'placeholder'   => 'Elasticsearch',
                'required'      => true
            )
        );

        $this->addElement(
            'select',
            'instance',
            array(
                'description'   => $this->translate('Elasticsearch instance'),
                'label'         => $this->translate('Elasticsearch Instance'),
                'multiOptions'  => (new Instances())->select(['name', 'name'])->fetchPairs(),
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'index',
            array(
                'description'   => $this->translate('Elasticsearch index pattern'),
                'label'         => $this->translate('Index'),
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'filter',
            array(
                'description'   => $this->translate('Elasticsearch filter in the Icinga Web 2 filter format'),
                'label'         => $this->translate('Filter'),
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'fields',
            array(
                'description'   => $this->translate(
                    'Comma-separated list of field names to display. The @timestamp field is always included'
                ),
                'label'         => $this->translate('Fields'),
                'required'      => true
            )
        );
    }

    protected function createInsertElements(array $formData)
    {
        $this->createBaseElements($formData);

        $this->setTitle($this->translate('Create a New Event Type'));

        $this->setSubmitLabel($this->translate('Save'));
    }

    protected function createUpdateElements(array $formData)
    {
        $this->createBaseElements($formData);

        $this->setTitle(sprintf($this->translate('Update Event Type %s'), $this->getIdentifier()));

        $this->addElement(
            'submit',
            'btn_submit',
            [
                'decorators'            => ['ViewHelper'],
                'ignore'                => true,
                'label'                 => $this->translate('Save')
            ]
        );

        $this->addElement(
            'submit',
            'btn_remove',
            [
                'decorators'            => ['ViewHelper'],
                'ignore'                => true,
                'label'                 => $this->translate('Remove')
            ]
        );

        $this->addDisplayGroup(
            ['btn_submit', 'btn_remove'],
            'form-controls',
            [
                'decorators' => [
                    'FormElements',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ]
            ]
        );
    }

    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove Event Type %s'), $this->getIdentifier()));

        $this->setSubmitLabel($this->translate('Yes'));
    }

    protected function createFilter()
    {
        return Filter::where('name', $this->getIdentifier());
    }

    protected function getInsertMessage($success)
    {
        return $success
            ? $this->translate('Event type created')
            : $this->translate('Failed to create Event type');
    }

    protected function getUpdateMessage($success)
    {
        return $success
            ? $this->translate('Event type updated')
            : $this->translate('Failed to update Event type');
    }

    protected function getDeleteMessage($success)
    {
        return $success
            ? $this->translate('Event type removed')
            : $this->translate('Failed to remove Event type');
    }
}
