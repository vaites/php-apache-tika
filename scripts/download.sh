#!/usr/bin/env bash

BINARIES=${APACHE_TIKA_BINARIES:-bin}
VERSION=${APACHE_TIKA_VERSION:-"1.18"}
LATEST="1.18"

mkdir --parents $BINARIES

if [ $VERSION == $LATEST ]; then
   MIRROR="http://www-us.apache.org"
else
   MIRROR="https://archive.apache.org"
fi

if [ ! -f "$BINARIES/tika-app-$VERSION.jar" ]; then
    wget "$MIRROR/dist/tika/tika-app-$VERSION.jar" -O "$BINARIES/tika-app-$VERSION.jar"
fi

if [ ! -f "$BINARIES/tika-server-$VERSION.jar" ]; then
    wget "$MIRROR/dist/tika/tika-server-$VERSION.jar" -O "$BINARIES/tika-server-$VERSION.jar"
fi