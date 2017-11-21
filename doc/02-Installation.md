# Installation

## Requirements

* Icinga Web 2 >= 2.4.2
* PHP version 5.6.x or 7.x
* php-curl
* Elasticsearch >= 5.x

## Installation
As with any Icinga Web 2 module, installation is pretty straight-forward. You just have to drop the module into the
`/usr/share/icingaweb2/mdouels/elasticsearch` directory. Please note that the directory name **must** be `elasticsearch`
and nothing else. If you want to use a different directory, make sure it is within the module path of Icinga Web 2.

```shell
git clone https://github.com/icinga/icingaweb2-module-elasticsearch.git /usr/share/icingaweb2/modules/elasticsearch
```

The module can be enabled through the web interface (`Configuration -> Modules -> elasticsearch`) or via the CLI:

```shell
icingacli module enable elasticsearch
```

## Configuration
Before the Elasticsearch mdoule can display anything, you have to configure at least one instance and one event type.
Read the [Configuration](03-Configuration.md) section for details.