#!/usr/bin/env bash

if [ ! -z "$(git status --porcelain)" ]; then
    printf "You have uncommitted changes\n"
    exit
fi

CURRENT=$(git describe --tags `git rev-list --tags --max-count=1`)

read -p "new version: "  version_new

if [ -z $version_new ]; then
    printf "specify a new version!\n"
    exit
fi

sed -i '.bak' "s/$version_old/$version_new/" bin/project
sed -i '.bak' "s/$version_old/$version_new/" composer.json

rm -f composer.json.bak bin/project.bak

box compile

printf "commit your changes and create a new release!\n"
