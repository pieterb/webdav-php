<?php
/**
 * Contains tests for the DAV_Request_PUT class
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
 * Contains tests for the DAV_Request_PUT class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_PUTTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_PUT  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/collection/new_resource';
    $this->obj = DAV_Request::inst();
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
  }


//  public function testConstructor() {
//    $this->assertNull( $this->obj->range_start, 'DAV_Request_PUT constructor should have NULL as start byte if no Content-Range header is present' );
//    $this->assertNull( $this->obj->range_end  , 'DAV_Request_PUT constructor should have NULL as end byte if no Content-Range header is present' );
//    $this->assertNull( $this->obj->range_total, 'DAV_Request_PUT constructor should have NULL as total number of bytes if no Content-Range header is present' );
//
//    $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 500-999/1234';
//    $this->obj = DAV_Request::inst();
//    $this->assertSame( 500,  $this->obj->range_start, 'DAV_Request_PUT constructor should parse start byte of Content-Range correctly' );
//    $this->assertSame( 999,  $this->obj->range_end  , 'DAV_Request_PUT constructor should parse end byte of Content-Range correctly' );
//    $this->assertSame( 1234, $this->obj->range_total, 'DAV_Request_PUT constructor should parse total number of bytes of Content-Range correctly' );
//
//    // Return a 400 status code if the Content-Range header is wrong
//    $_SERVER['HTTP_CONTENT_RANGE'] = 'characters 1-2/3';
//    $this->expectOutputString( <<<EOS
//Content-Type: text/plain; charset="UTF-8"
//HTTP/1.1 400 Bad Request
//HTTP/1.1 400 Bad Request
//Can't understand Content-Range: characters 1-2/3
//EOS
//    );
//    DAV_Request::inst();
//  }
//
//
//  public function testHandleNonExisting() {
//    // Unable to PUT file in non-existing collection
//    DAV::$REGISTRY->setResourceClass( null );
//
//    $this->expectOutputString( <<<EOS
//Content-Type: text/plain; charset="UTF-8"
//HTTP/1.1 409 Conflict
//HTTP/1.1 409 Conflict
//Unable to PUT file in non-existing collection.
//EOS
//    );
//    $this->obj->handleRequest();
//  }
//
//
//  public function testHandleCollection() {
//    // Method PUT not supported on collections
//    DAV::$REGISTRY->setResourceClass( new DAVACL_Test_Collection( $_SERVER['REQUEST_URI'] ) );
//    $this->expectOutputString( <<<EOS
//Content-Location: http://example.org/collection/new_resource/
//Content-Type: text/plain; charset="UTF-8"
//HTTP/1.1 405 Method Not Allowed
//HTTP/1.1 405 Method Not Allowed
//Method PUT not supported on collections.
//EOS
//    );
//    $this->obj->handleRequest();
//  }


  public function testHandleWithoutRange() {
    // Check without range (test for set_getcontenttype() en set_getcontentlanguage())
    $map = array(
        array( '/collection/new_resource', new DAVACL_Test_Resource( '/collection/new_resource' ) ),
        array( '/collection', new DAVACL_Test_Collection( '/collection' ) )
    );
    DAV::$REGISTRY = $this->getMock( 'DAV_Test_Registry', array( 'resource' ) );
    DAV::$REGISTRY->expects( $this->any() )
                  ->method( 'resource' )
                  ->will( $this->returnValueMap( $map ) );

    $this->expectOutputString( <<<EOS
DAVACL_Test_Resource::storeProperties() called for /collection/new_resource
DAVACL_Test_Resource::method_PUT() called for /collection/new_resource
HTTP/1.1 204 No Content

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleWithRange() {
    // Check with range
    $map = array(
        array( '/collection/new_resource', new DAVACL_Test_Resource( '/collection/new_resource' ) ),
        array( '/collection', new DAVACL_Test_Collection( '/collection' ) )
    );
    DAV::$REGISTRY = $this->getMock( 'DAV_Test_Registry', array( 'resource' ) );
    DAV::$REGISTRY->expects( $this->any() )
                  ->method( 'resource' )
                  ->will( $this->returnValueMap( $map ) );

    $this->expectOutputString( <<<EOS
DAVACL_Test_Resource::method_PUT_range() called for /collection/new_resource
HTTP/1.1 204 No Content

EOS
    );
    $_SERVER['HTTP_CONTENT_RANGE'] = 'bytes 500-999/1234';
    $this->obj = DAV_Request::inst();
    $this->obj->handleRequest();
  }

} // Class DAV_Request_PUT

// End of file