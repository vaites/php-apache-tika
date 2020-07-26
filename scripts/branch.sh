#!/usr/bin/env bash

rm -rf composer.lock
rm -rf vendor
git checkout $1
composer install