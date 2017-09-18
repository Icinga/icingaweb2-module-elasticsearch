<?php

namespace Icinga\Module\Elasticsearch\Clicommands;

use Exception;
use Icinga\Cli\Command;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Elasticsearch\Elastic;
use Icinga\Module\Elasticsearch\FilterRenderer;
use Icinga\Module\Elasticsearch\Instances;

class CheckCommand extends Command
{
    public static $states = [
        0 => 'OK',
        1 => 'WARNING',
        2 => 'CRITICAL',
        3 => 'UNKNOWN'
    ];

    /**
     * Count Elasticsearch events in a certain time period and report warning or critical according to the given thresholds
     *
     * USAGE:
     *
     *   icingacli elasticsearch check [options]
     *
     * OPTIONS:
     *
     *   --instance     Name of the configure Elasticsearch instace
     *
     *   --index        Elasticsearch index pattern
     *
     *   --filter       Elasticsearch filter in the Icinga Web 2 URL filter format
     *
     *   --crit         Critical threshold
     *
     *   --warn         Warning threshold
     *
     *   --from         English textual representation of the start timestamp. Defaults to -5m.
     *
     * Note: these options are the same as in the web frontend!
     */
    public function defaultAction()
    {
        $instance = (new Instances())
            ->select()
            ->where('name', $this->params->getRequired('instance'))
            ->fetchRow();

        if ($instance === false) {
            $this->exitPlugin(3, 'Instance not found');
        }

        $crit = (int) $this->params->getRequired('crit');
        $warn = (int) $this->params->getRequired('warn');

        $index = $this->params->getRequired('index');

        $filter = Filter::matchAll(
            Filter::expression('@timestamp', '>=', 'now' . $this->params->get('from', '-5m')),
            Filter::fromQueryString($this->params->getRequired('filter'))
        );

        $agg = [
            'aggs' => [
                'count' => [
                    'value_count' => [
                        'field' => '@timestamp'
                    ]
                ]
            ]
        ];

        try {
            $response = (new Elastic($instance))
                ->select()
                ->from($index)
                ->filter((new FilterRenderer($filter))->getQuery())
                ->patch($agg)
                ->limit(0)
                ->getResponse();
        } catch (Exception $e) {
            $this->exitPlugin(3, $e->getMessage());
        }

        /** @var array $response */
        $count = $response['aggregations']['count']['value'];

        $message = sprintf('%d hits', $count);

        if ($count >= $crit) {
            $this->exitPlugin(2, $message);
        }

        if ($count >= $warn) {
            $this->exitPlugin(1, $message);
        }

        $this->exitPlugin(0, $message);
    }

    public function exitPlugin($status, $message)
    {
        printf('%s - %s%s', self::$states[$status], $message, PHP_EOL);

        exit($status);
    }
}
