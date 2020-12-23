#!/usr/bin/env bash

PREVIOUS=${1}
VERSION=${2}

sed -i "s/$PREVIOUS/$VERSION/" bin/project
sed -i "s/$PREVIOUS/$VERSION/" composer.json

box compile
