#!/usr/bin/env bash

BINARIES=${APACHE_TIKA_BINARIES:-bin}
VERSION=${APACHE_TIKA_VERSION:-"1.19.1"}
MIRROR="https://archive.apache.org"

mkdir --parents $BINARIES

if [ ! -f "$BINARIES/tika-app-$VERSION.jar" ]; then
    wget "$MIRROR/dist/tika/tika-app-$VERSION.jar" -O "$BINARIES/tika-app-$VERSION.jar"
fi

if [ ! -f "$BINARIES/tika-server-$VERSION.jar" ]; then
    wget "$MIRROR/dist/tika/tika-server-$VERSION.jar" -O "$BINARIES/tika-server-$VERSION.jar"
fi
