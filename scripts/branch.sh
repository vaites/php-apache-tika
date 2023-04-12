#!/usr/bin/env bash

rm -rf vendor
rm -f composer.lock
git checkout $1
php bin/composer.phar install
