<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms\Config;

use Icinga\Web\Notification;
use Icinga\Forms\ConfigForm;

class ElasticsearchConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_logstash_elasticsearch');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        $this->config->setSection('elasticsearch', $this->getValues());

        if ($this->save()) {
            Notification::success($this->translate('New Elasticsearch configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * @see Form::onRequest()
     */
    public function onRequest()
    {
        $this->populate($this->config->getSection('elasticsearch')->toArray());
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'url',
            array(
                'value'         => 'http://elasticsearch:9200',
                'label'         => $this->translate('Elasticsearch URL'),
                'description'   => $this->translate('URL to your Elasticsearch installation.')
            )
        );
        $this->addElement(
            'text',
            'index_pattern',
            array(
                'value'         => 'logstash-*',
                'label'         => $this->translate('Logstash index pattern'),
                'description'   => $this->translate(
                    'The index pattern of your Logstash data inside Elasticsearch.'
                    . ' A similar setting has to be configured in Kibana on first use!'
                )
            )
        );
    }
}
