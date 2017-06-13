#!/usr/bin/env bash

set -e

ICINGAWEB_CONFIG=/etc/icingaweb2

: ${ELASTICSEARCH_URL:=http://elasticsearch:9200}
: ${ELASTICSEARCH_USER:=}
: ${ELASTICSEARCH_PASSWORD:=}
: ${ELASTICSEARCH_INDEX_PATTERN:="logstash-*"}

test -d "$ICINGAWEB_CONFIG/modules/elasticsearch" || mkdir "$ICINGAWEB_CONFIG/modules/elasticsearch"

echo "Updating $ICINGAWEB_CONFIG/modules/elasticsearch/config.ini"
cat > "$ICINGAWEB_CONFIG/modules/elasticsearch/config.ini" <<EOF
[elasticsearch]
url = "${ELASTICSEARCH_URL}"
username = "${ELASTICSEARCH_USER}"
password = "${ELASTICSEARCH_PASSWORD}"
index_pattern = "${ELASTICSEARCH_INDEX_PATTERN}"
EOF

if [ ! -e "$ICINGAWEB_CONFIG/enabledModules/elasticsearch" ]; then
  echo "Enabling module elasticsearch"
  icingacli module enable elasticsearch
fi
