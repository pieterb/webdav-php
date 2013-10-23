#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )/.."
./vendor/bin/phpunit --bootstrap ./tests/bootstrap.php ./tests
