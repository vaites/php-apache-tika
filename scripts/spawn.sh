#!/usr/bin/env bash

JARS=${APACHE_TIKA_JARS:-bin}

declare -a SUPPORTED_VERSIONS=("1.7" "1.8" "1.9" "1.10" "1.11" "1.12" "1.13")

PORT=9998
for VERSION in "${SUPPORTED_VERSIONS[@]}"
do
   java -jar "$JARS/tika-server-$VERSION.jar" -p $PORT 2> /dev/null &
   ((PORT++))
done

sleep 10