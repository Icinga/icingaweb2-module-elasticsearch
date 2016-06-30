<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;
use Icinga\Web\Form;

/**
 * EventType form for configuration
 */
class EventTypeForm extends RepositoryForm
{
    /**
     * {@inheritdoc}
     */
    protected function createInsertElements(array $formData)
    {
        $this->setTitle($this->translate('Create event type'));

        $this->addElement('text', 'name',
            array(
                'label' => $this->translate('Name'),
            )
        );

        $this->createUpdateElements($formData);

        $this->setSubmitLabel($this->translate('Add'));
    }

    /**
     * {@inheritdoc}
     */
    protected function createUpdateElements(array $formData)
    {
        if ($this->mode === self::MODE_UPDATE) {
            $this->setTitle(sprintf($this->translate('Edit event type "%s"'), $this->getIdentifier()));
            $this->setSubmitLabel($this->translate('Save'));
        }

        $this->addElement('text', 'label',
            array(
                'label' => $this->translate('Label'),
            )
        );
        $this->addElement('text', 'description',
            array(
                'label' => $this->translate('Description'),
            )
        );

        // TODO: we need to offer a better form for this - like the FilterEditor widget
        $this->addElement('text', 'filter',
            array(
                'label'       => $this->translate('Filter'),
                'description' => '(An Icingaweb 2 compatible URL filter) ' .
                    $this->translate('To restrict what can be seen via this type')
            )
        );
        $this->addElement('text', 'fields',
            array(
                'label'       => $this->translate('Fields'),
                'description' => $this->translate('A comma separated list of columns to list in the table'),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeleteElements(array $formData)
    {
        $this->setTitle(sprintf($this->translate('Remove event type %s?'), $this->getIdentifier()));
        $this->setSubmitLabel($this->translate('Yes'));
    }

    /**
     * {@inheritdoc}
     */
    protected function createFilter()
    {
        return Filter::where('name', $this->getIdentifier());
    }

    /**
     * {@inheritdoc}
     */
    protected function getInsertMessage($success)
    {
        if ($success) {
            return $this->translate('Event type added successfully');
        } else {
            return $this->translate('Failed to add event type');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getUpdateMessage($success)
    {
        if ($success) {
            return sprintf($this->translate("Event type '%s' updated successfully"), $this->getIdentifier());
        } else {
            return sprintf($this->translate("Failed to edit event type '%s'!"), $this->getIdentifier());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeleteMessage($success)
    {
        if ($success) {
            return sprintf($this->translate("Event type '%s' deleted successfully"), $this->getIdentifier());
        } else {
            return sprintf($this->translate("Failed to delete event type '%s'!"), $this->getIdentifier());
        }
    }
}
