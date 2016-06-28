#!/usr/bin/env bash

JARS=${APACHE_TIKA_JARS:-bin}

declare -a SUPPORTED_VERSIONS=("1.7" "1.8" "1.9" "1.10" "1.11" "1.12" "1.13")

for VERSION in "${SUPPORTED_VERSIONS[@]}"
do
   if [ ! -f "$JARS/tika-app-$VERSION.jar" ]; then
        wget "https://archive.apache.org/dist/tika/tika-app-$VERSION.jar" -O "$JARS/tika-app-$VERSION.jar"
   fi

   if [ ! -f "$JARS/tika-server-$VERSION.jar" ]; then
        wget "https://archive.apache.org/dist/tika/tika-server-$VERSION.jar" -O "$JARS/tika-server-$VERSION.jar"
   fi
done