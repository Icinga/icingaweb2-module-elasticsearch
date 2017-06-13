# Development environment

## Running in Docker

You can pull up a quick ELK test environment in Docker with the
included `docker-compose.example.yml`.

```
cp docker-compose.example.yml docker-compose.yml

docker-compose up web
```

This brings you:

* Elasticsearch [http://localhost:9200](http://localhost:9200)
* Icingaweb2 with this module [http://localhost:8080](http://localhost:8080)

Icingaweb2 should be set up with the correct settings, and the credentials:

    Username: icingaadmin
    Password: icinga

## Kibana

You can bring up Kibana for your tests as well:

```
docker-compose up kibana
```

Access the Kibana Dashboard at: [http://localhost:5601](http://localhost:5601)

## Logstash

To get in some log data, Logstash is prepared to write syslog to Elasticsearch.
 
```
docker-compose up kibana
```
 
Configure a local rsyslogd to send logs to Logstash:

**/etc/rsyslog.d/logstash-local.conf**

    *.* @127.0.0.1:1514
    
Or manually:

    logger -n 127.0.0.1 -P 1514 -t elastictest "My test message"

## References

Documentation regarding ElasticStack in Docker (for development purposes here):

* [Install Elasticsearch with Docker](https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html)
* [Running Kibana on Docker](https://www.elastic.co/guide/en/kibana/current/docker.html)
* [Running Logstash on Docker](https://www.elastic.co/guide/en/logstash/current/docker.html)
