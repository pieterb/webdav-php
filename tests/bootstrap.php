<?php
/**
 * Bootstraps the test environment
 * 
 * Copyright ©2013 SURFsara b.v., Amsterdam, The Netherlands
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package DAV
 * @subpackage tests
 */

DAV::$testMode = true; // Turn on test mode, so headers won't be sent, because sending headers won't work as all tests are run from the commandline

$_SERVER = array();
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SCRIPT_NAME'] = 'bootstrap.php'; // Strange enough, PHPunit seems to use this, so let's set it to some value
$_SERVER['SERVER_NAME'] = 'example.org';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/path';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:24.0) Gecko/20100101 Firefox/24.0';

function loadMocks() {
  $mockPath = realpath( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'mocks' . DIRECTORY_SEPARATOR;
  // These two are required by other classes, so let's load them now manually so they are there at least in time
  require_once( $mockPath . 'DAVACL_Test_Resource.php' );
  require_once( $mockPath . 'DAVACL_Test_Collection.php' );
  $dir = new DirectoryIterator( $mockPath );
  foreach ( $dir as $file ) {
    if ( ( substr( $file, 0, 1 ) !== '.' ) && ( substr( $file, -4 ) === '.php' ) ) {
      require_once( $mockPath . $file );
    }
  }
}

loadMocks();

// End of file