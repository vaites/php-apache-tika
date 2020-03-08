#!/usr/bin/env bash

rm -rf vendor
git checkout $1
composer install