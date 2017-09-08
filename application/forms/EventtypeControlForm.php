<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Module\Elasticsearch\Eventtypes;
use Icinga\Web\Form;

class EventtypeControlForm extends Form
{
    public function init()
    {
        $this->setAttrib('class', 'eventtype-control');
        $this->setAttrib('data-base-target', '_self');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'eventtype',
            array(
                'autosubmit'    => true,
                'label'         => $this->translate('Event Type'),
                'multiOptions'  => (new Eventtypes())->select(['name', 'name'])->fetchPairs(),
                'value'         => $this->getRequest()->getUrl()->getParam('eventtype', '')
            )
        );
    }

    public function getRedirectUrl()
    {
        return $this->getRequest()->getUrl()
            ->setParam('eventtype', $this->getElement('eventtype')->getValue());
    }

    /**
     * Control is always successful
     *
     * @return  bool
     */
    public function onSuccess()
    {
        return true;
    }
}
