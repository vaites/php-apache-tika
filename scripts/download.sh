#!/usr/bin/env bash

BINARIES=${APACHE_TIKA_BINARIES:-bin}

declare -a SUPPORTED_VERSIONS=("1.7" "1.8" "1.9" "1.10" "1.11" "1.12" "1.13" "1.14" "1.15")

for VERSION in "${SUPPORTED_VERSIONS[@]}"
do
   if [ ! -f "$BINARIES/tika-app-$VERSION.jar" ]; then
        wget "https://archive.apache.org/dist/tika/tika-app-$VERSION.jar" -O "$BINARIES/tika-app-$VERSION.jar"
   fi

   if [ ! -f "$BINARIES/tika-server-$VERSION.jar" ]; then
        wget "https://archive.apache.org/dist/tika/tika-server-$VERSION.jar" -O "$BINARIES/tika-server-$VERSION.jar"
   fi
done