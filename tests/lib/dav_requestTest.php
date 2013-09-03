<?php
/**
 * Contains tests for the DAV_Request class
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
 * Contains tests for the DAV_Request class
 * @package DAV
 * @subpackage tests
 */
class DAV_RequestTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'PROPFIND';
    $_SERVER['REQUEST_URI'] = '/path/to/resource.txt';
    $_SERVER['HTTP_DESTINATION'] = 'http://example.org/destination/path';
    $_SERVER['SERVER_NAME'] = 'example.org';
    $_SERVER['SERVER_PORT'] = 80;
    $_SERVER['HTTP_OVERWRITE'] = 'F';
    $_SERVER['HTTP_DEPTH'] = 0;
  }


  public function testDepth() {
    $obj = DAV_Test_Request::inst();
    $this->assertEquals( 0, $obj->depth(), 'DAV_Request::depth() should return 0 if that\'s the value of the Depth header' );
    $_SERVER['HTTP_DEPTH'] = 1;
    $this->assertEquals( 1, $obj->depth(), 'DAV_Request::depth() should return 1 if that\'s the value of the Depth header' );
    $_SERVER['HTTP_DEPTH'] = 'infinity';
    $this->assertEquals( 'infinity', $obj->depth(), 'DAV_Request::depth() should return \'infinite\' if that\'s the value of the Depth header' );
    $_SERVER['HTTP_DEPTH'] = 666;
    try {
      $obj->depth();
      $this->assertTrue( false, 'DAV_Request::depth() should throw an exception if the value of the Depth header is not valid' );
    }catch ( Exception $e ) {
      if ( $e instanceof DAV_Status ) {
        $this->assertEquals( DAV::HTTP_BAD_REQUEST, $e->getCode(), 'DAV_Request::depth() should throw a DAV_Status exception with the 400 Bad Request code if the value of the Depth header is not valid' );
      }else{
        $this->assertTrue( false, 'DAV_Request::depth() should throw a DAV_Status exception if the value of the Depth header is not valid' );
      }
    }
  }


  public function testDestination() {
    $this->assertEquals( '/destination/path', DAV_Request::destination(), 'DAV_Request::destination() should return the correct destination' );
  }


  public function testEqualETags() {
    $etag1 = 'W/"123456789"';
    $etag2 = '"987654321"';
    $this->assertTrue(  DAV_Request::equalETags( $etag1, $etag1 ), 'DAV_Request::equalETags() should return true when an ETag is compared to itself' );
    $this->assertFalse( DAV_Request::equalETags( $etag1, $etag2 ), 'DAV_Request::equalETags() should return false when two different ETag are compared' );
  }


  public function testHandleRequest() {
    DAV::$REGISTRY = new DAV_Test_Registry();
    $obj = DAV_Test_Request::inst();
    $this->expectOutputString( "DAV_Request::handle() called for path: /path/to/resource.txt\nContent-Location: http://example.org/new_folder_without_trailing_slash/\nDAV_Request::handle() called for path: /new_folder_without_trailing_slash\n" );
    $obj->handleRequest();
    
    $_SERVER['REQUEST_METHOD'] = 'MKCOL';
    $_SERVER['REQUEST_URI'] = '/new_folder_without_trailing_slash';
    $obj->handleRequest();
  }
  

  public function testInst() {
    $this->assertInstanceOf( 'DAV_Request_PROPFIND', DAV_Request::inst(), 'DAV_Request::inst() should return an instance of the correct class' );
  }


  public function testOverwrite() {
    $obj = DAV_Test_Request::inst();
    $this->assertFalse( $obj->overwrite(), 'DAV_Request::overwrite() should return false when the Overwrite header is set to F' );
    $_SERVER['HTTP_OVERWRITE'] = 'T';
    $this->assertTrue(  $obj->overwrite(), 'DAV_Request::overwrite() should return true when the Overwrite header is set to T' );
    unset( $_SERVER['HTTP_OVERWRITE'] );
    $this->assertTrue(  $obj->overwrite(), 'DAV_Request::overwrite() should return true when the Overwrite header is not set' );
  }

} // class DAV_Request


/**
 * Implements the abstract classes of DAV_Request for testing purposes
 * @package DAV
 * @subpackage tests
 */
class DAV_Test_Request extends DAV_Request {

  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }


  protected function handle( $resource ) {
    print( 'DAV_Request::handle() called for path: ' . $resource->path . "\n" );
  }

} // class DAV_Test_Request

class DAV_Test_Registry implements DAV_Registry {

  public function resource( $path ) {
    return new DAV_Resource( $path );
  }


  public function forget( $path ) {
  }


  public function shallowLock( $write_paths, $read_paths ) {
  }


  public function shallowUnlock() {
  }
  
}

// End of file