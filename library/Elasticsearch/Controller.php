<?php
/* Elasticsearch for Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Elasticsearch;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Elasticsearch\Web\Widget\AutoRefresher;
use Icinga\Module\Elasticsearch\Web\Widget\FieldSelector;
use Icinga\Repository\RepositoryQuery;
use Icinga\Web\Controller as IcingaWebController;
use Icinga\Web\Url;

class Controller extends IcingaWebController
{
    /**
     * Helper method to instanciate tabs
     *
     * @param  string $category    Name of the tabs category (internal label in this function)
     * @param  string $active      Which tab to activate (internal name in this function)
     * @param  Url    $active_url  Overwrite the URL of the active tab
     *
     * @throws ProgrammingError    When you select a category the function doesn't know
     */
    protected function createTabs($category=null, $active=null, $active_url=null)
    {
        $tabs = $this->getTabs();

        switch ($category) {
            case 'main':
                $tabs->add('overview', array(
                    'title' => $this->translate('Elasticsearch Overview'),
                    'url' => $this->view->url('elasticsearch')
                ));
                if ($active === null) {
                    $active = 'overview';
                }
                break;
            case 'events':
                if ($active === 'create') {
                    $tabs->add('create', array(
                        'title' => $this->translate('Create type'),
                        'url'   => $this->view->url('elasticsearch/types/create'),
                    ));
                    break;
                }
                $type = $this->getParam('type');
                $tabs->add('list', array(
                    'title' => $this->translate('Events'),
                    'url'   => $this->view->url('elasticsearch/events', array('type' => $type)),
                ));
                // TODO: only when config is allowed
                $tabs->add('edit', array(
                    'title' => $this->translate('Edit type'),
                    'url'   => $this->view->url('elasticsearch/types/edit', array('type' => $type)),
                ));
                break;
            case 'event':
                $tabs->add('show', array(
                    'title' => $this->translate('Event details'),
                ));
                break;
            case 'types':
                $tabs->add('index', array(
                    'title' => $this->translate('Event types'),
                    'url'   => $this->view->url('elasticsearch/types'),
                ));
                break;
            default:
                throw new ProgrammingError('tab category %s is not implemented');
        }

        if ($active !== null) {
            $tabs->activate($active);
            $it = $tabs->get($active);
            if ($it->getUrl() === null) {
                if ($active_url != null) {
                    $it->setUrl($active_url);
                }
                else {
                    $it->setUrl($this->getRequest()->getUrl());
                }
            }
        }
    }

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
