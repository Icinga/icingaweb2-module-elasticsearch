<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Elasticsearch\Web\Widget\AutoRefresher;
use Icinga\Module\Elasticsearch\Web\Widget\FieldSelector;
use Icinga\Repository\RepositoryQuery;
use Icinga\Web\Controller as IcingaWebController;

class Controller extends IcingaWebController
{
    /**
     * Sets up the FieldSelector Widget for the current view
     *
     * @param  RepositoryQuery $query
     * @return $this
     */
    public function setupFieldSelectorControl(RepositoryQuery $query)
    {
        if (! $this->view->compact) {
            $widget = new FieldSelector();
            $widget->setFieldsAvailable($query->getColumns());
            $this->view->fieldSelector = $widget;
        }
        return $this;
    }

    /**
     * Sets up the AutoRefresher Widget for the current view
     *
     * @param    int    $defaultRefresh   Default auto-refresh interval
     *
     * @return   $this
     */
    public function setupAutoRefresherControl($defaultRefresh=null)
    {
        $widget = new AutoRefresher();
        if ($defaultRefresh !== null) {
            $widget->setDefaultRefresh($defaultRefresh);
        }

        if (! $this->view->compact) {
            $this->view->autorefresher = $widget;
        }

        $interval = (int) $this->getRequest()->getParam('refresh', $widget->getDefaultRefresh());
        if ($interval !== null && $interval > 0) {
            $this->setAutorefreshInterval($interval);
        }

        return $this;
    }
}
