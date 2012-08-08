#!/bin/bash

cd "$( dirname '$0' )/../"
DIRNAME="$PWD"
mkdir docs 2>/dev/null
rm -rf docs/* 2>/dev/null

phpdoc \
  --filename 'lib/**/*.php' \
  --target "${DIRNAME}/docs/devel" \
  --output HTML:frames:default \
  --parseprivate on \
  --sourcecode on \
  --defaultpackagename DAV_Server \
  --title "DAV_Server Documentation"

mkdir docs/user
phpdoc \
  --filename 'dav/*.php' \
  --target "${DIRNAME}/docs/user" \
  --output HTML:frames:default \
  --parseprivate off \
  --sourcecode on \
  --defaultpackagename DAV_Server \
  --title "DAV_Server Documentation"
