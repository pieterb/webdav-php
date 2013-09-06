#!/bin/bash

set -e

cd "$( dirname "$0" )/../"
rm -rf docs 2>/dev/null || true
mkdir docs

vendor/bin/phpdoc.php

exit 0