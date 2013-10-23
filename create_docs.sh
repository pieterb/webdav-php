set -e

# First let's make sure we're in the right directory and a tools directory is available
cd "$( dirname "${BASH_SOURCE[0]}" )"
rm -rf docs docs-parser 2>/dev/null | true
./vendor/bin/phpdoc.php # configuration is stored in phpdoc.dist.xml
rm -rf docs-parser 2>/dev/null | true

# Everything worked out fine
exit 0
