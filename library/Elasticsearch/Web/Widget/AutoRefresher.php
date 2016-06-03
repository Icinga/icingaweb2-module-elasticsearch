<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch\Web\Widget;

use Icinga\Web\Widget\AbstractWidget;
use Icinga\Module\Elasticsearch\Forms\Widget\AutoRefresherControlForm;

/**
 * AutoRefresher control widget
 */
class AutoRefresher extends AbstractWidget
{
    /**
     * Default auto refresh interval for this instance
     *
     * @var int|null
     */
    protected $defaultRefresh;

    /**
     * Get the default interval
     *
     * @return int|null
     */
    public function getDefaultRefresh()
    {
        return $this->defaultRefresh;
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
    public function render()
    {
        $control = new AutoRefresherControlForm();
        $control
            ->setDefaultRefresh($this->defaultRefresh)
            ->handleRequest();
        return (string)$control;
    }
}
