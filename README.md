Icingaweb2 Elasticsearch Integration
====================================

This is a very early test module for Icingaweb2, offering Logstash Event integration.

It is planned to have the following features:
* Event search/display in the web interface
* CLI check command for Icinga(2)
* Integration into service details
* Search configuration coming from icinga2 service.vars

## Disclaimer

Be WARNED, this module is not yet tested against big Elasticsearch installations, it might cause problems there.

Neither is this module currently supported by anyone, use at your own risk.

## Development environment

You can pull up a quick ELK test environment in Docker with the included [docker-compose File](docker-compose.yml).

After firing that up you will have:

* Elasticsearch [http://localhost:9200](http://localhost:9200)
* Kibana Dashboard [http://localhost:5601](http://localhost:5601)
* Logstash prepared for syslog (tcp and udp on port `1514`)
* Icingaweb2 with this module [http://localhost:8080](http://localhost:8080)
  (setup token: docker) (auto-http login as icingaadmin - please select external auth)

You can fire syslogs to get actual data into ELK:

**/etc/rsyslog.d/logstash-local.conf**

    *.* @127.0.0.1:1514
    
Or manually:

    logger -n 127.0.0.1 -

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
