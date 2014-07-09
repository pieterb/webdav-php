<?php
/**
 * Contains tests for the DAV_Request_MOVE class
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
 * Contains tests for the DAV_Request_MOVE class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_MOVETest extends PHPUnit_Framework_TestCase {
  
  /**
   * @var  DAV_Request_MOVE  The object we will test
   */
  private $obj;
  
  
  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'MOVE';
    $_SERVER['HTTP_DEPTH'] = 'infinity';
    $_SERVER['HTTP_DESTINATION'] = '/new/destination';
    $_SERVER['REQUEST_URI'] = '/path';
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $this->obj = DAV_Request::inst();
    DAV::$LOCKPROVIDER = null;
  }


  public function testDepth() {
    $_SERVER['HTTP_DEPTH'] = '0';
    $this->assertSame( '0', $this->obj->depth(), 'DAV_Request_MOVE::depth() should return the Depth header correctly' );
    unset( $_SERVER['HTTP_DEPTH'] );
    $this->assertSame( DAV::DEPTH_INF, $this->obj->depth(), 'DAV_Request_MOVE::depth() should return \'infinity\' if the Depth header is not set' );
  }


  public function testHandleRoot() {
    $_SERVER['REQUEST_URI'] = '/';
    
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 403 Forbidden
HTTP/1.1 403 Forbidden

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleWrongDepth() {
    // Assert proper Depth: header value
    $_SERVER['HTTP_DEPTH'] = '0';
    $this->expectOutputString(<<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
Illegal value for Depth: header.
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleExternalURI() {

    // Move to an external URI?
//    $_SERVER['HTTP_DESTINATION'] = 'http://other_host.org/some/location';
//    $this->obj->handleRequest();
  }


  public function testHandleToParent() {
    // Check: Won't move a resource to one of its parents.
    $_SERVER['HTTP_DESTINATION'] = dirname( $_SERVER['REQUEST_URI'] );
    try {
      $this->obj->handleRequest();
      $this->assertTrue( false, 'DAV_Request_MOVE::handle() should throw a DAV_Status exception when trying to move a resource to one of its parents' );
    }catch ( PHPUnit_Framework_Error_Warning $exception ) {
      if ( $exception->getCode() !== 512 ) {
        $this->assertTrue( false, 'DAV_Request_MOVE::handle() should throw a DAV_Status exception with code 512 when trying to move a resource to one of its parents' );
      }
    }
  }


  public function testHandleToMember() {
    // Check: Won't move a resource to one of its members.
    $_SERVER['REQUEST_URI'] .= '/';
    $_SERVER['HTTP_DESTINATION'] = $_SERVER['REQUEST_URI'] . 'member/';
    
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 403 Forbidden
HTTP/1.1 403 Forbidden
Can't move a collection to itself or one of its members.
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleToUnexisting() {
    // Unable to MOVE to unexisting destination collection
    // Make a return map so the registry will return null when de collection of the destination is requested
    $returnMap = array();
    $returnMap[] = array( $_SERVER['REQUEST_URI'], new DAVACL_Test_Resource( $_SERVER['REQUEST_URI'] ) );
    $returnMap[] = array( dirname( $_SERVER['REQUEST_URI'] ), null );
    $tempRegistry = DAV::$REGISTRY;
    DAV::$REGISTRY = $this->getMock( 'DAV_Registry' );
    DAV::$REGISTRY->expects( $this->any() )
                  ->method( 'resource' )
                  ->will( $this->returnValueMap( $returnMap ) );

    $this->expectOutputString(<<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 409 Conflict
HTTP/1.1 409 Conflict
Unable to COPY to unexisting destination collection
EOS
    );
    $this->obj->handleRequest();

    DAV::$REGISTRY = $tempRegistry;
  }


  public function testHandleOverwrite() {
    // Check whether overwrite is prevented with HTTP header Overwrite: F
    $this->expectOutputString(<<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 412 Precondition Failed
HTTP/1.1 412 Precondition Failed

EOS
    );
    $_SERVER['HTTP_OVERWRITE'] = 'F';
    $this->obj->handleRequest();
  }


  public function testHandleNormal() {
    // Check if moving goes right when everything is correct
    $this->expectOutputString(<<<EOS
DAVACL_Test_Collection::method_MOVE() called for / and parameters path - /new/destination
HTTP/1.1 204 No Content

EOS
    );
    $this->obj->handleRequest();
  }

} // class DAV_Request_MOVETest

// End of file