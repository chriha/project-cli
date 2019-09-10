#!/usr/bin/env bash

mkdir -p bin
box build
mv project.phar bin/project
