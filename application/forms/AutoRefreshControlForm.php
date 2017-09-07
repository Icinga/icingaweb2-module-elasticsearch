<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Web\Form;

/**
 * Auto refresh control form
 */
class AutoRefreshControlForm extends Form
{
    /**
     * CSS class for the auto refresh control
     *
     * @var string
     */
    const CSS_CLASS_AUTOREFRESH = 'auto-refresh-control';

    /**
     * Default interval
     *
     * @var int
     */
    const DEFAULT_INTERVAL = 15;

    /**
     * Selectable intervals
     *
     * @var int[]
     */
    public static $intervals = array(
        15  => '15s',
        30  => '30s',
        60  => '1m',
        120 => '2m',
        300 => '5m'
    );

    /**
     * Default interval for this instance
     *
     * @var int|null
     */
    protected $defaultRefresh;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setAttrib('class', static::CSS_CLASS_AUTOREFRESH);
    }

    /**
     * Get the default inteval
     *
     * @return int
     */
    public function getDefaultRefresh()
    {
        return $this->defaultRefresh !== null ? $this->defaultRefresh : static::DEFAULT_INTERVAL;
    }

    /**
     * Set the default interval
     *
     * @param   int $defaultRefresh
     *
     * @return  $this
     */
    public function setDefaultRefresh($defaultRefresh)
    {
        $this->defaultRefresh = (int) $defaultRefresh;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl()
    {
        return $this->getRequest()->getUrl()
            ->setParam('refresh', $this->getElement('refresh')->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'refresh',
            array(
                'autosubmit'    => true,
                'escape'        => false,
                'label'         => $this->getView()->icon('cw'),
                'multiOptions'  => static::$intervals,
                'value'         => $this->getRequest()->getUrl()->getParam('refresh', $this->getDefaultRefresh()),
            )
        );
        $this->getElement('refresh')->getDecorator('label')->setOption('escape', false);
    }

    /**
     * Auto refresh control is always successful
     *
     * @return bool
     */
    public function onSuccess()
    {
        return true;
    }
}
