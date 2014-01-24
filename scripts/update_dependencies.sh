set -e

# First let's make sure we're in the right directory
cd "$( dirname "${BASH_SOURCE[0]}" )/.."

# Then install Composer and let it install dependencies for this project
php tools/composer.phar self-update
php tools/composer.phar update

# Everything worked out fine
exit 0
