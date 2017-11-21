<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Forms;

use Icinga\Web\Form;

/**
 * Auto refresh control form
 */
class AutorefreshControlForm extends Form
{
    /**
     * CSS class for the auto refresh control
     *
     * @var string
     */
    const CSS_CLASS_AUTOREFRESH = 'auto-refresh-control';

    /**
     * Auto-refresh interval
     *
     * @var int
     */
    protected $interval;

    /**
     * Selectable intervals
     *
     * @var int[]
     */
    public static $intervals = array(
        1  => '1s',
        10 => '10s',
        30 => '30s',
        60 => '1m'
    );

    public function init()
    {
        $this->setAttrib('class', static::CSS_CLASS_AUTOREFRESH);
    }

    /**
     * Get the auto-refresh interval
     *
     * @return  int
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Set the auto-refresh interval
     *
     * @param   int $interval
     *
     * @return  $this
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    public function getRedirectUrl()
    {
        return $this->getRequest()->getUrl()
            ->setParam('refresh', $this->getElement('refresh')->getValue());
    }

    public function createElements(array $formData)
    {
        $intervals = static::$intervals;

        $value = $this->getInterval();

        if (! isset($intervals[$value])) {
            $intervals[$value] = "{$value}s";
        }

        $this->addElement(
            'select',
            'refresh',
            array(
                'autosubmit'    => true,
                'label'         => $this->getView()->icon('cw'),
                'multiOptions'  => $intervals,
                'value'         => $value
            )
        );

        $this->getElement('refresh')->getDecorator('label')->setOption('escape', false);
    }

    /**
     * Auto-refresh control is always successful
     *
     * @return  bool
     */
    public function onSuccess()
    {
        return true;
    }
}
