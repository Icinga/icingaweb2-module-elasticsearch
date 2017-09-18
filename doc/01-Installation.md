# Installation

As with any Icinga Web 2 module, installation is pretty straight-forward. In case you're installing it from source all
you have to do is to drop the elasticsearch module in one of your module paths. You can configure the module paths
via  the web interface beneath `Configuration -> Application`. In a typical environment you'll probably drop the module
to `/usr/share/icingaweb2/modules/elasticsearch`. Please note that the directory name MUST be `elasticsearch` and not
icingaweb2-module-elasticsearch or anything else. After that please enable the module via the web interface or CLI.
