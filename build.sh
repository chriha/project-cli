#!/usr/bin/env bash

VERSION=${1}

sed -i "s/CURRENT_VERSION/$VERSION/" bin/project
sed -i "s/CURRENT_VERSION/$VERSION/" composer.json

composer install
box compile
