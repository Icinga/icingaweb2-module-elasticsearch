<?php

namespace Icinga\Module\Elasticsearch\Clicommands;

use Icinga\Cli\Command;
use Icinga\Exception\IcingaException as Exception;
use Icinga\Module\Elasticsearch\Search;

/**
 * Usage: icingacli elasticsearch check <options>
 */
class CheckCommand extends Command {

    protected $elasticsearch_url;
    protected $index_pattern;

    protected $stateMap = [
        0 => 'OK',
        1 => 'WARNING',
        2 => 'CRITICAL',
        3 => 'UNKNOWN'
    ];

    protected function pluginError($message) {
        $this->pluginExit($message, 3);
    }

    protected function pluginExit($message, $status=3, $perfdata=array()) {
        printf("%s - %s\n",
            isset($this->stateMap[$status]) ? $this->stateMap[$status] : 'UNKNOWN',
            trim($message)
        );

        // TODO: perfdata
        exit($status);
    }

    public function init() {
        $this->elasticsearch_url = $this->Config()->get('elasticsearch', 'url');
        $this->index_pattern = $this->Config()->get('elasticsearch', 'index_pattern', 'logstash-*');

        if (!$this->elasticsearch_url)
            $this->pluginError("No elasticsearch URL configured!");
    }

    /**
     * Check for Elasticsearch Events that might be a warning or a critical for Icinga
     *
     * Usage: icingacli elasticsearch check <options>
     *
     * Options:
     *   --query    Elasticsearch query string for events
     *   --filter   Elasticsearch filter string for the queried events
     *   --fields   A comma separated list of fields to show in the output (optional)
     *   --warning  Filter string to match warning events (similar to Elasticsearch)
     *   --critical Filter string to match critical events (similar to Elasticsearch)
     *   --list     List found events in command output
     *
     * Note: these options are the same as in the web frontend!
     */
    public function defaultAction() {

        $query = $this->params->get('query');
        $filter = $this->params->get('filter');
        $fields = $this->params->get('fields');
        $warning = $this->params->get('warning');
        $critical = $this->params->get('critical');
        $list = $this->params->get('list');

        // internal limit - try not to temper with it
        $limit = 1000;

        try {
            $search = new Search($this->elasticsearch_url."/".$this->index_pattern);

            if ($query)
                $search->setQueryString($query);
            else
                throw new Exception("You must provide a query with --query!");

            if ($filter)
                $search->setFilterQueryString($filter);

            if ($warning)
                $search->setIcingaWarningQuery($warning);
            if ($critical)
                $search->setIcingaCriticalQuery($critical);
            if (!$warning and !$critical)
                throw new Exception("You must provide at least one warning or critical filter with --warning / --critical!");

            $search->setFilteredByIcingaQueries(true);

            $search->setWithoutAck(true);
            $search->limit($limit);

            $events = $search->fetchAll();

            $count = $search->count();
            $warning = $search->getIcingaWarningCount();
            $critical = $search->getIcingaCriticalCount();

            $message = sprintf("Found %d events, %d warning, %d critical",
                $count,
                $warning,
                $critical
            );

            if ($warning > 0)
                $status = 1;
            elseif ($critical > 0)
                $status = 2;
            else
                $status = 0;

            if ($count > $limit) {
                $message .= sprintf(", we found more than %d events, Icinga State calculation is incorrect!",
                    $limit
                );
                $status = 3;
            }

            $long = array();
            if ($list and $fields) {
                $fieldlist = preg_split('/\s*,\s*/', trim($fields));
                foreach($events as $event) {
                    $line = array();
                    foreach($fieldlist as $field) {
                        $line[] = (isset($event[$field]) ? $event[$field] : '-');
                    }
                    $long[] = join(" ", $line);
                }
            }
            if (count($long) > 0)
                $message .= "\n".join("\n", $long);

            $this->pluginExit($message, $status);
        }
        catch (Exception $e) {
            $this->pluginError($e->getMessage());
        }

    }

}