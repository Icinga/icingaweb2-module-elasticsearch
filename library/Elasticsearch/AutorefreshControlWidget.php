<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Elasticsearch\Forms\AutorefreshControlForm;
use Icinga\Web\Widget\AbstractWidget;

/**
 * Auto-refresh control widget
 */
class AutorefreshControlWidget extends AbstractWidget
{
    /**
     * Auto-refresh interval
     *
     * @var int
     */
    protected $interval;

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

    public function render()
    {
        $control = new AutorefreshControlForm();
        $control
            ->setInterval($this->getInterval())
            ->handleRequest();

        return (string) $control;
    }
}
