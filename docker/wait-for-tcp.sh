#!/bin/bash

set -e

HOST="$1"
PORT="$2"

if [ -z "$HOST" ]; then
    echo "Please specify host!" >&2
    exit 1
fi

if [ -z "$PORT" ]; then
    echo "Please specify port!" >&2
    exit 1
fi

count=0
while ! timeout 1 bash -c "cat < /dev/null > '/dev/tcp/$HOST/$PORT'" &>/dev/null
do
    echo "Waiting for service $HOST:$PORT to become available"
    sleep 1
    : $((count++))
    if [ $count -gt 20 ]; then
        echo "Waiting for TCP port timed out!"
        exit 2
    fi
done

exit 0
