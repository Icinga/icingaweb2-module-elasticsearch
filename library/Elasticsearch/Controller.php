<?php
/* Icinga Web 2 Elasticsearch Module (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Data\Paginatable;
use Icinga\Web\Widget\Paginator;

class Controller extends \Icinga\Web\Controller
{
    /**
     * Set up the auto-refresh widget for the current view
     *
     * @param    int    $interval   Default auto-refresh interval
     *
     * @return   $this
     */
    public function setupAutorefreshControl($interval)
    {
        $widget = new AutorefreshControlWidget();

        if (! $this->view->compact) {
            $this->view->autorefreshControl = $widget;
        }

        $interval = (int) $this->getRequest()->getParam('refresh', $interval);

        if ($interval !== null && $interval > 0) {
            $widget->setInterval($interval);
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

    public function paginate(Paginatable $paginatable, $itemsPerPage = 25, $pageNumber = 0)
    {
        $request = $this->getRequest();
        $limit = $request->getParam('limit', $itemsPerPage);
        $page = $request->getParam('page', $pageNumber);
        $paginatable->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        if (! $this->view->compact) {
            $paginator = new Paginator();
            $paginator->setQuery($paginatable);
            $this->view->paginator = $paginator;
        }

        return $this;
    }
}
