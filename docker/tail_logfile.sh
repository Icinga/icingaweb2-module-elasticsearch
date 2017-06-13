#!/usr/bin/env bash

set -e

LOGFILE=/tmp/icingaweb2.log

: >"$LOGFILE"
chown -R www-data "$LOGFILE"
tail -F "$LOGFILE" &
