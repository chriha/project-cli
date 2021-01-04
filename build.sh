#!/usr/bin/env bash

VERSION=${1}

sed -i '.bak' "s/CURRENT_VERSION/$VERSION/" bin/project
sed -i '.bak' "s/CURRENT_VERSION/$VERSION/" composer.json

composer install
box compile
