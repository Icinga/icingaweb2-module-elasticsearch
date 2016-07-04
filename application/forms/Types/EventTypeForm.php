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
        $this->addElement('text', 'hostmap_filter', array(
            'label' => $this->translate('Elasticsearch filter'),
        ));

        // TODO: note in ZF < 1.12
        // TODO: decor for backticks
        $this->addElement('note', 'hostmap_filter_note', array(
            'value' =>
                $this->translate('The Elasticsearch filter can utilize a syntax like ${attribute} to access Icingaweb2 host attributes.').' '.
                '<pre>logsource=${host_name}</pre>'.
                $this->translate('In addition one can use some basic regex to manipulate the attribute. This is a similar syntax to bash.').' '.
                '<pre>logsource=${host_name/\.example\.com/}</pre>'.
                '<pre>${attribute/&lt;regex&gt;/&lt;replacement&gt;/&lt;options&gt;}</pre>',
            'decorators' => array(
                'ViewHelper',
                array(
                    'HtmlTag',
                    array('tag' => 'p')
                )
            )
        ));

        $this->addDisplayGroup(
            array('hostmap_filter', 'hostmap_filter_note'),
            'hostmap_group',
            array(
                'legend' => $this->translate('Find events for Icinga hosts'),
                'decorators' => array('FormElements', 'Fieldset'),
            )
        );
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
//        $this->onSuccessExpressionSelector('elasticsearch');
//        $this->onSuccessExpressionSelector('icinga');

        return parent::onSuccess();
    }
}
