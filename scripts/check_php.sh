#!/bin/bash

cd $(dirname '$0')
cd ..
echo 'url(en/de)code instead of rawurl(en/de)code:'
grep -P '\burl(en|de)code\b' *.php
[ $? -eq 0 ] || echo OK
echo
echo 'DAV::$REGISTRY instead of SD_Registry::inst():'
grep -P 'DAV::\$REGISTRY' sd*.php
[ $? -eq 0 ] || echo OK
