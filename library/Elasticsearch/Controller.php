<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Module\Elasticsearch\Forms\Widget\AutoRefresherControlForm;

class Controller extends \Icinga\Web\Controller
{
    /**
     * Sets up the AutoRefresher Widget for the current view
     *
     * @param    int    $defaultRefresh   Default auto-refresh interval
     *
     * @return   $this
     */
    public function setupAutoRefresherControl($defaultRefresh=null)
    {
        $widget = new AutoRefresherControlForm();
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

    /**
     * Set the title tab
     *
     * @param   string  $label
     */
    public function setTitle($label)
    {
        $this->getTabs()->add(uniqid(), [
            'active'    => true,
            'label'     => $label,
            'url'       => $this->getRequest()->getUrl()
        ]);
    }
}
