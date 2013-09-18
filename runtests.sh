#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
./vendor/bin/phpunit --bootstrap ${DIR}/tests/bootstrap.php ${DIR}/tests
