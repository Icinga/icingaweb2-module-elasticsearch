#!/usr/bin/env bash

: ${DATABASE:=mysql}
: ${DATABASE_HOST:=db}
: ${DATABASE_PORT:=}

if [ -z "$DATABASE_PORT" ]; then
  case "$DATABASE" in
    mysql) DATABASE_PORT=3306 ;;
    pgsql) DATABASE_PORT=5342 ;;
    *) echo "Unknown database type: $DATABASE" >&2; exit 1 ;;
  esac
fi

$(dirname "$0")/wait-for-tcp.sh "${DATABASE_HOST}" "${DATABASE_PORT}"
