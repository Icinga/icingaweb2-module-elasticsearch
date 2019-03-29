# Icinga 2 Checks

This module brings an `icingacli` command to check if certain events are present in Elasticsearch.

## Example

The following will check if there are more than 3 (warning) or 5 (critical) events of severity `critical` from the host `www.example.com` in the data from the last hour.

* The `instance` is the same which was set in the modules configuration
* The values of `crit` and `warn` are just numerical thresholds
* `index` is set to an index pattern in Elasticsearch. It's a pattern that has to match all index names to search
* As a `filter` the check takes a filter in Icinga Web 2's filter syntax. These are comparisons of fields in Elasticsearch to values

```
# icingacli elasticsearch check --instance elasticsearch --crit 5 --warn 3 --index logstash* --filter "beat.hostname=www.example.com AND severity=critical" --from -1h 
```
