<?php
/**
 * Contains tests for the DAV_Request_MKCOL class
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
 * Contains tests for the DAV_Request_MKCOL class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_MKCOLTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_MKCOL  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'MKCOL';
    $this->obj = DAV_Request::inst();
    DAV::$REGISTRY->setResourceClass( 'DAV_Resource' );
  }


  public function testHandleExistingResource() {
    // If the location already exists, you're not allowed to do a MKCOL request on it
    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 405 Method Not Allowed
HTTP/1.1 405 Method Not Allowed

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleUnexistingParent() {
    // If the parent doesn't exist, we can't make a subcollection
    DAV::$REGISTRY->setResourceClass( null );
    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 409 Conflict
HTTP/1.1 409 Conflict
Unable to MKCOL in unknown resource
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleNoBody() {
    // We do not allow a body for the MKCOL request
    $_SERVER['CONTENT_LENGTH'] = 10;
    DAV::$REGISTRY->setResourceClass( array( null, null, 'DAVACL_Test_Collection' ) );
    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 415 Unsupported Media Type
HTTP/1.1 415 Unsupported Media Type

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleNormal() {
    DAV::$REGISTRY->setResourceClass( array( null, null, 'DAVACL_Test_Collection' ) );
    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/
DAVACL_Test_Collection::method_MKCOL() called for / and parameter path
Content-Type: text/plain; charset=US-ASCII
Location: http://example.org/path
HTTP/1.1 201 Created
/path
EOS
    );
    $this->obj->handleRequest();
  }

} // Class DAV_Request_MKCOL

// End of file