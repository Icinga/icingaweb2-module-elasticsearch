<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms\Config;

use Icinga\Application\Config;
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
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'elasticsearch_url',
            array(
                'allowEmpty'    => true,
                'placeholder'   => 'http://localhost:9200',
                'label'         => $this->translate('Elasticsearch URL'),
                'description'   => $this->translate('URL to your Elasticsearch cluster')
            )
        );
        $this->addElement(
            'text',
            'elasticsearch_username',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Username'),
                'description'   => $this->translate('The user name to use for authentication')
            )
        );
        $this->addElement(
            'password',
            'elasticsearch_password',
            array(
                'renderPassword'    => true,
                'allowEmpty'        => true,
                'label'             => $this->translate('Password'),
                'description'       => $this->translate('The password to use for authentication')
            )
        );
        $this->addElement(
            'text',
            'elasticsearch_certificate_path',
            array(
                'allowEmpty'    => true,
                'label'         => $this->translate('Certificate Path'),
                'description'   => $this->translate('The path to the Elasticsearch\'s certificate if HTTPS is used')
            )
        );
        $this->addElement(
            'text',
            'elasticsearch_index_pattern',
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

    /**
     * {@inheritdoc}
     */
    protected function writeConfig(Config $config)
    {
        // TODO: Remove this once #11743 is fixed
        $section = $config->getSection('elasticsearch');
        foreach ($section->toArray() as $key => $value) {
            if ($value === null) {
                unset($section->{$key});
            }
        }

        parent::writeConfig($config);
    }
}
