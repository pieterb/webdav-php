set -e

# First let's make sure we're in the right directory and a tools directory is available
cd "$( dirname "${BASH_SOURCE[0]}" )/.."
rm -rf tools 2>/dev/null | true
mkdir tools

# Then install Composer and let it install dependencies for this project
curl -sS https://getcomposer.org/installer | php -- --install-dir=tools
php tools/composer.phar install

# Everything worked out fine
exit 0