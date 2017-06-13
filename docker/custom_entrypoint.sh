#!/usr/bin/env bash

set -e

SCRIPT_HOME="$(dirname "$0")"
"$SCRIPT_HOME"/provision_config.sh
"$SCRIPT_HOME"/provision_elasticsearch_config.sh
"$SCRIPT_HOME"/wait-for-database.sh
"$SCRIPT_HOME"/setup_database.php
"$SCRIPT_HOME"/tail_logfile.sh

exec /entrypoint.sh "$@"