#!/usr/bin/env bash

PORT=9998
BINARIES=${APACHE_TIKA_JARS:-bin}
VERSION=${APACHE_TIKA_VERSION:-"1.15"}

RUNNING=`ps aux | grep -c tika-server-$VERSION`

if [ $RUNNING -lt 2 ]; then
    echo "Starting Tika Server $VERSION"
    java -jar "$BINARIES/tika-server-$VERSION.jar" -p $PORT 2> /tmp/tika-server-$VERSION.log &
    ((PORT++))
    sleep 5
else
    echo "Tika Server $VERSION already running"
fi