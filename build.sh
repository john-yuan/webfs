#!/bin/sh

readonly __PWD__=$(pwd)
readonly __DIR__=$(cd $(dirname $0) && pwd && cd $__PWD__)

readonly DIST_DIR="${__DIR__}/dist"

# Change the directory to the directory of this file.
cd $__DIR__

# Remove the old files.
if [ -d "${DIST_DIR}" ]
then
    rm -rf "${DIST_DIR}/core"
    rm -rf "${DIST_DIR}/service"
else
    mkdir $DIST_DIR
fi

cp -r "${__DIR__}/core" $DIST_DIR
cp -r "${__DIR__}/service" $DIST_DIR

# Remove the test services in dist directory
rm -rf "${DIST_DIR}/service/test"

# Copy the production config to config.php
cat "${DIST_DIR}/core/config_production.php" > "${DIST_DIR}/core/config.php"
