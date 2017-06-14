#!/usr/bin/env bash

set -e

ICINGAWEB_CONFIG=/etc/icingaweb2

test -d "$ICINGAWEB_CONFIG/modules/monitoring" || mkdir "$ICINGAWEB_CONFIG/modules/monitoring"

echo "Updating $ICINGAWEB_CONFIG/modules/monitoring/config.ini"
cat > "$ICINGAWEB_CONFIG/modules/monitoring/config.ini" <<EOF
[ido]
type = "ido"
resource = "icingaweb_db"
EOF

if [ ! -e "$ICINGAWEB_CONFIG/enabledModules/monitoring" ]; then
  echo "Enabling module monitoring"
  icingacli module enable monitoring
fi
