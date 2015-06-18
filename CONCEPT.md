Icinga2 Logstash concept
========================

We want to be able to monitor events.

Events can be quite different, but we want to be able to query for events and alert when they occur.


## Icinga2 config


``` icinga2
object CheckCommand "icingaweb2_logstash" {
    import "plugin-check-command"
    
    command = [ '/usr/bin/icingacli', 'logstash', 'check' ]
    
    arguments = {
        "-I" = "$logstash_index$"
        "-Q" = "$logstash_query$"
        "-F" = "$logstash_filter$"
        "-f" = "$logstash_fields$"
    }
    
    vars.logstash_index = "logstash-*"
}
```

``` icinga2
apply Service "apache events" {
  import "generic-service"
  
  check_command = "icingaweb2_logstash"
  
  vars.logstash_query = "host:" + host.name
  vars.logstash_fields = "host,

}
```

