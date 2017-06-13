#!/usr/bin/env bash

set -e

ICINGAWEB_CONFIG=/etc/icingaweb2
LOGFILE=/tmp/icingaweb2.log

: ${LOGLEVEL:=INFO}
: ${DATABASE:=mysql}
: ${DATABASE_HOST:=db}
: ${DATABASE_PORT:=}
: ${DATABASE_NAME:=icingaweb2}
: ${DATABASE_USER:=icingaweb2}
: ${DATABASE_PASSWORD:=icingaweb2}
: ${DATABASE_CHARSET:=}

echo "Updating $ICINGAWEB_CONFIG/config.ini"
cat > "$ICINGAWEB_CONFIG/config.ini" <<EOF
[global]
show_stacktraces = "1"
config_backend = "db"
config_resource = "icingaweb_db"

[logging]
log = "file"
file = "${LOGFILE}"
level = "${LOGLEVEL}"
EOF

echo "Updating $ICINGAWEB_CONFIG/authentication.ini"
cat > "$ICINGAWEB_CONFIG/authentication.ini" <<EOF
[icingaweb2]
backend = "db"
resource = "icingaweb_db"
EOF

echo "Updating $ICINGAWEB_CONFIG/groups.ini"
cat > "$ICINGAWEB_CONFIG/groups.ini" <<EOF
[icingaweb2]
backend = "db"
resource = "icingaweb_db"
EOF

echo "Updating $ICINGAWEB_CONFIG/roles.ini"
cat > "$ICINGAWEB_CONFIG/roles.ini" <<EOF
[admins]
users = "icingaadmin"
permissions = "*"
groups = "admins"
EOF

echo "Updating $ICINGAWEB_CONFIG/resources.ini"
cat > "$ICINGAWEB_CONFIG/resources.ini" <<EOF
[icingaweb_db]
type = "db"
db = "${DATABASE}"
host = "${DATABASE_HOST}"
port = "${DATABASE_PORT}"
dbname = "${DATABASE_NAME}"
username = "${DATABASE_USER}"
password = "${DATABASE_PASSWORD}"
charset = "${DATABASE_CHARSET}"
EOF

test -d "$ICINGAWEB_CONFIG/modules" || mkdir "$ICINGAWEB_CONFIG/modules"

if [ ! -e "$ICINGAWEB_CONFIG/enabledModules/doc" ]; then
  echo "Enabling module doc"
  icingacli module enable doc
fi
