<?php
/**
 * Contains tests for the DAV_Request_OPTIONS class
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

/**
 * Contains tests for the DAV_Request_OPTIONS class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_OPTIONSTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    DAV::$LOCKPROVIDER = null;
  }

  
  public function testHandle() {
    $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
    $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'LOCK';
    $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'Depth';
    $obj = DAV_Request::inst();
    
    $this->expectOutputString( <<<EOS
DAV: 1, 3
DAV: access-control
DAV: <http://apache.org/dav/propset/fs/1>
MS-Author-Via: DAV
Allow: ACL, COPY, DELETE, GET, HEAD, LOCK, MKCOL, MOVE, OPTIONS, POST, PROPFIND, PROPPATCH, PUT, REPORT, UNLOCK
Content-Length: 0
Access-Control-Allow-Methods: LOCK
Access-Control-Allow-Headers: Depth

EOS
    );
    $obj->handleRequest();
  }

} // Class DAV_Request_OPTIONS

// End of file