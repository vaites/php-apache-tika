#!/usr/bin/env bash

PORT=${APACHE_TIKA_PORT:-9998}
BINARIES=${APACHE_TIKA_BINARIES:-bin}
VERSION=${APACHE_TIKA_VERSION:-"1.24.1"}

{
    java --add-modules java.se.ee -version &&
    JAVA='java --add-modules java.se.ee'
} || {
    JAVA='java'
}

if [ $(ps aux | grep -c tika-server-$VERSION) -lt 2 ]; then
    $JAVA -version

    if [ $(php -r "echo version_compare('$VERSION', '1.14', '>') ? 'true' : 'false';") == "true" ]; then
        COMMAND="$JAVA -jar $BINARIES/tika-server-$VERSION.jar -p $PORT -enableUnsecureFeatures -enableFileUrl"
    else
        COMMAND="$JAVA -jar $BINARIES/tika-server-$VERSION.jar -p $PORT"
    fi

    if [ "$1" == "--foreground" ]; then
        echo "Starting Tika Server $VERSION in foreground"
        $COMMAND
    else
        echo "Starting Tika Server $VERSION in background"
        $COMMAND  2> /tmp/tika-server-$VERSION.log &
        sleep 5
    fi
else
    echo "Tika Server $VERSION already running"
fi
