#!/usr/bin/env bash

#if [ ! -z "$(git status --porcelain)" ]; then
#    printf "You have uncommitted changes\n"
#    exit
#fi

CURRENT=$(git describe --tags `git rev-list --tags --max-count=1`)

read -p "new version: "  NEW

if [ -z "$NEW" ]; then
    printf "specify a new version!\n"
    exit
fi

sed -i '.bak' "s/$CURRENT/$NEW/" bin/project
sed -i '.bak' "s/$CURRENT/$NEW/" composer.json

rm -f composer.json.bak bin/project.bak

box compile

./project --version

printf "commit your changes and create a new release!\n"
