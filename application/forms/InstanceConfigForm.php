<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;
use Icinga\Module\Elasticsearch\Instances;

/**
 * Create, update and delete Elasticsearch instances
 */
class InstanceConfigForm extends RepositoryForm
{
    public function init()
    {
        $this->repository = new Instances();
        $this->redirectUrl = 'elasticsearch/instances';
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
            $this->setRedirectUrl("elasticsearch/instances/delete?instance={$this->getIdentifier()}");
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
                'description'   => $this->translate('Name of the Elasticsearch instance'),
                'label'         => $this->translate('Instance Name'),
                'placeholder'   => 'Elasticsearch',
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'uri',
            array(
                'description'   => $this->translate('URI to the Elasticsearch instance'),
                'label'         => $this->translate('URI'),
                'placeholder'   => 'http://localhost:9200',
                'required'      => true
            )
        );
    }

    protected function createInsertElements(array $formData)
    {
        $this->createBaseElements($formData);

        $this->setTitle($this->translate('Create a New Instance'));

        $this->setSubmitLabel($this->translate('Save'));
    }

    protected function createUpdateElements(array $formData)
    {
        $this->createBaseElements($formData);

        $this->setTitle(sprintf($this->translate('Update Instance %s'), $this->getIdentifier()));

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
        $this->setTitle(sprintf($this->translate('Remove Instance %s'), $this->getIdentifier()));

        $this->setSubmitLabel($this->translate('Yes'));
    }

    protected function createFilter()
    {
        return Filter::where('name', $this->getIdentifier());
    }

    protected function getInsertMessage($success)
    {
        return $success
            ? $this->translate('Elasticsearch instance created')
            : $this->translate('Failed to create Elasticsearch instance');
    }

    protected function getUpdateMessage($success)
    {
        return $success
            ? $this->translate('Elasticsearch instance updated')
            : $this->translate('Failed to update Elasticsearch instance');
    }

    protected function getDeleteMessage($success)
    {
        return $success
            ? $this->translate('Elasticsearch instance removed')
            : $this->translate('Failed to remove Elasticsearch instance');
    }
}
