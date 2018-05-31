#  Icinga Web2 Elasticsearch Module

Elasticsearch is a powerful search engine based on Apache Lucene. It stores and indexes data and provides access to it
via a REST API. Elasticsearch often is used to store logging data, received from a central log 
management software such as Logstash, Filebeat or Graylog.

The Elasticsearch module for Icinga Web 2 gives you access to this data, embedded in your Icinga Web 2 interface.
Custom filters allow you to limit the data that should be displayed. You can give your users access to 
certain data types without revealing everything stored in Elasticsearch. Multiple Elasticsearch instances can be
configured and accessed either without authentication, HTTP basic authentication or certificates.

Read the [installation instructions](02-Installation.md) to get started.