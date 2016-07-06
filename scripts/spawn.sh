#!/usr/bin/env bash

JARS=${APACHE_TIKA_JARS:-bin}

declare -a SUPPORTED_VERSIONS=("1.13" "1.12" "1.11" "1.10" "1.9" "1.8" "1.7")

PORT=9998
for VERSION in "${SUPPORTED_VERSIONS[@]}"
do
    RUNNING=`ps aux | grep -c tika-server-$VERSION`

    if [ $RUNNING -lt 2 ]; then
        java -jar "$JARS/tika-server-$VERSION.jar" -p $PORT 2> /tmp/tika-server-$VERSION.log &
        ((PORT++))
        sleep 2
    else
        echo "Tika Server $VERSION already running"
    fi
done

sleep 10