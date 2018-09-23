#!/usr/bin/env bash

PORT=9998
BINARIES=${APACHE_TIKA_BINARIES:-bin}
VERSION=${APACHE_TIKA_VERSION:-"1.19"}

RUNNING=$(ps aux | grep -c tika-server-$VERSION)

if ! type "javac" 2> /dev/null; then
    JAVA='java --add-modules java.se.ee'
else
    JAVA='java'
fi

if [ $RUNNING -lt 2 ]; then
    $JAVA -version
    echo "Starting Tika Server $VERSION"

    if [ "$1" == "--foreground" ]; then
        MODE="foreground"
    else
        MODE="background"
    fi

    if [ $(echo "$VERSION > 1.14" | bc) -gt 0 ]; then
        COMMAND="$JAVA -jar $BINARIES/tika-server-$VERSION.jar -p $PORT -enableUnsecureFeatures -enableFileUrl"
    else
        COMMAND="$JAVA -jar $BINARIES/tika-server-$VERSION.jar -p $PORT"
    fi

    if [ $MODE == "background" ]; then
        $COMMAND  2> /tmp/tika-server-$VERSION.log &
        sleep 5
    else
        $COMMAND
    fi

else
    echo "Tika Server $VERSION already running"
fi
