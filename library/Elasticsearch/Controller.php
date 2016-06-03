<?php

namespace Icinga\Module\Elasticsearch;

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
}
