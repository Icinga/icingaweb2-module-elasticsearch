<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms\Types;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;
use Icinga\Module\Elasticsearch\EventBackend;
use Icinga\Web\Form;

/**
 * EventType form for configuration
 */
class EventTypeForm extends RepositoryForm
{
    const EXPRESSION = '__EXPRESSION__';

    /**
     * @var EventBackend
     */
    protected $eventRepository;

    /**
     * Return an instance of EventBackend
     *
     * @return EventBackend
     */
    public function eventRepository()
    {
        if ($this->eventRepository !== null) {
            return $this->eventRepository;
        }

        return $this->eventRepository = EventBackend::fromConfig();
    }

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
                'label' => $this->translate('Filter'),
                'description' => '(An Icingaweb 2 compatible URL filter) ' .
                    $this->translate('To restrict what can be seen via this type'),
                'required' => true,
            )
        );
        $this->addElement('text', 'fields',
            array(
                'label' => $this->translate('Fields'),
                'description' => $this->translate('A comma separated list of columns to list in the table'),
                'required' => true,
            )
        );

        $this->createHostmapElements($formData);
    }

    /**
     * Create elements for the Icinga host mapping feature
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createHostmapElements(array $formData)
    {
        $this->addElement('checkbox', 'hostmap_enabled', array(
            'label' => $this->translate('Enable'),
            'autosubmit' => true,
        ));

        $this->addDisplayGroup(
            array('hostmap_enabled'),
            'hostmap',
            array(
                'legend' => $this->translate('Match events to Icinga hosts'),
                'decorators' => array('FormElements', 'Fieldset'),
            )
        );

        // Only when user select's Icinga host mapping
        if (array_key_exists('hostmap_enabled', $formData) && $formData['hostmap_enabled'] == '1') {
            // Elasticsearch elements
            $elasticsearchFields = $this->eventRepository()->select()->getColumns();

            $elasticsearchFields = array_filter($elasticsearchFields, function($val) {
                $a = substr($val, 0, 1);
                if ($a === '_' || $a === '@') {
                    return false;
                }
                return true;
            });

            $this->createExpressionSelector($formData, 'elasticsearch', $elasticsearchFields, array(
                'select' => array(
                    'label' => $this->translate('Elasticsearch field'),
                    'description' => $this->translate('Elasticsearch field which is used to map the host name'),
                ),
                'expression' => array(
                    'label' => $this->translate('Elasticsearch expression'),
                    'description' => $this->translate('Document attributes can be accessed via ${attribute}'),
                ),
            ));

            // Icinga elements
            $icingaFields = array(
                'host_name',
                'host_display_name',
                'host_address',
                'host_address6',
            );
            $this->createExpressionSelector($formData, 'icinga', $icingaFields, array(
                'select' => array(
                    'label' => $this->translate('Icinga field'),
                    'description' => $this->translate('Icingaweb2 field which is used to map the host name'),
                ),
                'expression' => array(
                    'label' => $this->translate('Icinga expression'),
                    'description' => $this->translate('Icingaweb2 host attributes can be accessed via ${attribute} (e.g. ${host_address6} or a custom variable like ${_host_operatingsystem}'),
                ),
            ));
        }
    }

    /**
     * Helper for creating Expression / Selector input
     *
     * @param  array       $formData   The data sent by the user
     * @param  string      $id         The field group id
     * @param  array       $fields     Fields to offer the user for selection
     * @param  array|null  $options    Additional options for the elements (See code)
     */
    protected function createExpressionSelector(array $formData, $id, $fields, $options=null)
    {
        $selectorId = 'hostmap_'.$id;
        $expressionId = $selectorId.'_expression';
        
        $multiOptions = array(
            '' => sprintf('(%s)', $this->translate('Please choose')),
            self::EXPRESSION => sprintf('(%s)', $this->translate('Custom expression')),
        );

        foreach ($fields as $field) {
            $multiOptions[$field] = $field;
        }

        $selectOptions = array(
            'multiOptions' => $multiOptions,
            'required' => true,
            'autosubmit' => true,
        );
        if ($options !== null && array_key_exists('select', $options)) {
            $selectOptions = array_merge($selectOptions, $options['select']);
        }

        $this->addElement('select', $selectorId, $selectOptions);
        $this->getDisplayGroup('hostmap')->addElement($this->getElement($selectorId));

        // Decode an expression with an var we know back to the selector
        $showExpression = false;
        if (
            ! array_key_exists($selectorId, $formData)
            && array_key_exists($expressionId, $formData)
        ) {
            $value = $formData[$expressionId];
            $el = $this->getElement($selectorId);

            if (preg_match('/^\s*\${([^}]+)}\s*$/', $value, $match) && in_array($match[1], $fields)) {
                $el->setValue($match[1]);
            }
            else {
                $el->setValue(self::EXPRESSION);
                $showExpression = true;
            }
        }

        // Expression input - when selected
        if (
            $showExpression
            || (array_key_exists($selectorId, $formData) && $formData[$selectorId] === self::EXPRESSION)
        ) {
            $expressionOptions = array(
                'required' => true,
                'value' => '${' . self::EXPRESSION . '}',
            );
            if ($options !== null && array_key_exists('select', $options)) {
                $expressionOptions = array_merge($expressionOptions, $options['expression']);
            }

            $this->addElement('text', $expressionId, $expressionOptions);
            $this->getDisplayGroup('hostmap')->addElement($this->getElement($expressionId));
        }

    }

    /**
     * Process the expression selector before saving
     *
     * Writing the value from the selector to the expression field.
     *
     * @param  string  $id  The field group id
     */
    protected function onSuccessExpressionSelector($id)
    {
        $selectorId = 'hostmap_'.$id;
        $expressionId = $selectorId.'_expression';

        $value = $this->getValue($selectorId);
        $this->removeElement($selectorId);
        if ($value !== self::EXPRESSION) {
            $this->addElement('hidden', $expressionId, array(
                'value' => '${' . $value . '}',
            ));
        }
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

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $this->onSuccessExpressionSelector('elasticsearch');
        $this->onSuccessExpressionSelector('icinga');

        return parent::onSuccess();
    }
}
