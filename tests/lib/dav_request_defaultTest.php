<?php
/**
 * Contains tests for the DAV_Request_DEFAULT class
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
 * Contains tests for the DAV_Request_DEFAULT class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_DEFAULTTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @var  DAV_Request_DEFAULT  The object we will test
   */
  private $obj;
  
  
  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'UNKNOWN';
    $this->obj = DAV_Request::inst();
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
  }
  

  public function testHandle() {
    $this->expectOutputString( <<<EOS
Content-Type: Allow: ACL, COPY, DELETE, GET, HEAD, LOCK, MKCOL, MOVE, OPTIONS, POST, PROPFIND, PROPPATCH, PUT, REPORT, UNLOCK
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 405 Method Not Allowed
HTTP/1.1 405 Method Not Allowed
Allowed methods: ACL, COPY, DELETE, GET, HEAD, LOCK, MKCOL, MOVE, OPTIONS, POST, PROPFIND, PROPPATCH, PUT, REPORT, UNLOCK
EOS
    );
    $this->obj->handleRequest();
  }

} // Class DAV_Request_DEFAULT

// End of file