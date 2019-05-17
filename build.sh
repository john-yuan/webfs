#!/bin/sh

readonly __PWD__=$(pwd)
readonly __DIR__=$(cd $(dirname $0) && pwd && cd $__PWD__)

readonly SRC_DIR="${__DIR__}/src"
readonly DIST_DIR="${__DIR__}/dist"

rm -rf $DIST_DIR
cp -r $SRC_DIR $DIST_DIR

cd $DIST_DIR
rm -rf storage
rm -rf service/test

cat core/config_production.php > core/config.php
