<?php
/**
 * Contains tests for the DAV_Request_DELETE class
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
 * Contains tests for the DAV_Request_DELETE class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_DELETETest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_COPY  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_SERVER['HTTP_DEPTH'] = 'infinity';
    $_SERVER['REQUEST_URI'] = '/path/to/resource';
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $this->obj = DAV_Request::inst();
  }


  public function testDepth() {
    $_SERVER['HTTP_DEPTH'] = '0';
    $this->assertEquals( '0', $this->obj->depth(), 'DAV_Request_DELETE::depth() should return the Depth header correctly' );
    unset( $_SERVER['HTTP_DEPTH'] );
    $this->assertEquals( DAV::DEPTH_INF, $this->obj->depth(), 'DAV_Request_DELETE::depth() should return \'infinity\' if the Depth header is not set' );
  }


  public function testHandleWrongDepth() {
    $_SERVER['HTTP_DEPTH'] = 0;
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
Only Depth: infinity is allowed for DELETE requests.
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleNormal() {
    $this->expectOutputString( <<<EOS
DAVACL_Test_Collection::method_DELETE() called for /path/to and parameter /resource
HTTP/1.1 204 No Content

EOS
    );
    $this->obj->handleRequest();
  }


  public function testDeleteRoot() {
    $_SERVER['REQUEST_URI'] = '/';
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 403 Forbidden
HTTP/1.1 403 Forbidden

EOS
    );
    $this->obj->handleRequest();
  }


  public function testDeleteNormal() {
    // We use a stubb as resource so we can simulate a recursive delete
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Delete_Resource' );
    $_SERVER['REQUEST_URI'] = '/path/to/resource/';
    $this->expectOutputString( <<<EOS
DAVACL_Test_Collection::method_DELETE() called for /path/to/resource/subdir1/ and parameter subfile1
DAVACL_Test_Collection::method_DELETE() called for /path/to/resource/subdir1/ and parameter subfile2
DAVACL_Test_Collection::method_DELETE() called for /path/to/resource/ and parameter subdir1/
DAVACL_Test_Collection::method_DELETE() called for /path/to/resource/ and parameter subdir2/
DAVACL_Test_Collection::method_DELETE() called for /path/to/resource/ and parameter subdir3/
DAVACL_Test_Collection::method_DELETE() called for /path/to and parameter /resource/
HTTP/1.1 204 No Content

EOS
    );
    $this->obj->handleRequest();
  }

} // Class DAV_Request_DELETE


class DAVACL_Test_Delete_Resource extends DAVACL_Test_Collection {

  private $children = array();
  private $position = 0;


  public function __construct( $path ) {
    parent::__construct( $path );
    if ( $path === '/path/to/resource/' ) {
      $this->children = array( 'subdir1/', 'subdir2/', 'subdir3/' );
    }elseif ( $path === '/path/to/resource/subdir1/' ) {
      $this->children = array( 'subfile1', 'subfile2' );
    }
  }


  public function rewind() {
    $this->position = 0;
  }

  public function current() {
    return $this->children[ $this->position ];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    $this->position++;
  }

  public function valid() {
    return isset( $this->children[ $this->position ] );
  }

}


// End of file