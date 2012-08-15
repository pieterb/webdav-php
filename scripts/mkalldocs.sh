#!/bin/bash

cd "$( dirname "$0" )/../"
DIRNAME="$PWD"
mkdir docs 2>/dev/null
rm -rf docs/* 2>/dev/null

phpdoc \
  --directory 'lib' \
  --filename '*.php' \
  --target "${DIRNAME}/docs" \
  --parseprivate \
  --sourcecode \
  --defaultpackagename DAV_Server \
  --title "DAV_Server Documentation"

mkdir docs/user
phpdoc \
  --directory 'lib' \
  --filename '*.php' \
  --target "${DIRNAME}/docs/user" \
  --defaultpackagename DAV_Server \
  --title "DAV_Server Documentation"
