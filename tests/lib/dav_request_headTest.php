<?php
/**
 * Contains tests for the DAV_Request_HEAD class
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
 * Contains tests for the DAV_Request_HEAD class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_HEADTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_GET  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'HEAD';
    $headers = array();
    $headers['Content-Length'] = 100;
    $headers['Content-Type'] = 'text/plain';
    $headers['ETag'] = 'an ETag';
    $headers['Last-Modified'] = '11-12-13 14:15';
    $headers['Content-Language'] = 'nl';
    $headers['Accept-Ranges'] = 'bytes';
    $resource = $this->getMock( 'DAVACL_Test_Resource', array( 'method_HEAD' ), array( $_SERVER['REQUEST_URI'] ) );
    $resource->expects( $this->once() )
             ->method( 'method_HEAD' )
             ->will( $this->returnValue( $headers ) );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->obj = DAV_Request::inst();
  }


  public function testHandle() {
    $this->expectOutputString( <<<EOS
Content-Length: 100
Content-Type: text/plain
ETag: an ETag
Last-Modified: 11-12-13 14:15
Content-Language: nl
Accept-Ranges: bytes

EOS
    );
    $this->obj->handleRequest();
  }

} // Class DAV_Request_HEAD

// End of file