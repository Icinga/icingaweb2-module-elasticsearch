Icingaweb2 Logstash Integration
===============================

This is a very early test module for Icingaweb2, offering Logstash Event integration.

It is planned to have the following features:
* Event search/display in the web interface
* CLI check command for Icinga(2)
* Integration into service details
* Search configuration coming from icinga2 service.vars

## Disclaimer

Be WARNED, this module is not yet tested against big Elasticsearch installations, it might cause problems there.

Neither is this module currently supported by anyone, use at your own risk.

## Use the check in Icinga 2

Here is a example how to use the check in Icinga2:

``` icinga2
object CheckCommand "logstash_events" {
  import "plugin-check-command"

  command = [ "/usr/bin/icingacli", "logstash", "check" ]

  arguments = {
    "--query"    = "$logstash_query$"
    "--filter"   = "$logstash_filter$"
    "--fields"   = "$logstash_fields$"
    "--warning"  = "$logstash_warning$"
    "--critical" = "$logstash_critical$"
    "--list" = {
      set_if = "$logstash_list$"
    }
  }

  vars.logstash_list = false
}

apply Service "logstash syslog" {
  import "generic-service"

  check_command = "logstash_events"

  var hostname = host.name.split(".")

  vars.logstash_query = "type:syslog logsource:" + hostname[0]
  vars.logstash_filter = "NOT severity_label:(Informational OR Notice OR Emergency)"
  vars.logstash_fields = "@timestamp,severity_label,facility_label,message"
  vars.logstash_warning = "severity_label:Warning"
  vars.logstash_critical = "severity_label:(Alert Error Emergency Critical)"

  assign where host.kernel == "Linux"
}
```

## About

    Copyright (c) 2015-2016 Icinga Development Team (https://www.icinga.org/)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see http://www.gnu.org/licenses/
