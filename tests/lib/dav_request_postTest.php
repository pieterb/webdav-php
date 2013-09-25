<?php
/**
 * Contains tests for the DAV_Request_POST class
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
 * Contains tests for the DAV_Request_POST class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_POSTTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_GET  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $this->obj = DAV_Request::inst();
  }


  public function testHandleResourceWritesDirectly() {
    $resource = new DAVACL_Test_Post_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'direct' );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
DAVACL_Test_Post_Resource::method_POST() called with output as direct for resource /path
Content-Length: 89

EOS
    ); // Note that the headers are behind the body. This is because in test modus, headers will be printed and thus appear in the output buffer behind the body.
    $this->obj->handleRequest();
  }


  public function testHandleResourceReturnsString() {
    $resource = new DAVACL_Test_Post_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'string' );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
Content-Length: 89
DAVACL_Test_Post_Resource::method_POST() called with output as string for resource /path

EOS
    );
    $this->obj->handleRequest();
  }

} // Class DAV_Request_POST


class DAVACL_Test_Post_Resource extends DAVACL_Test_Resource {

  private $outputType = 'direct';


  public function setOutputType( $type ) {
    if ( $type === 'string' ) {
      $this->outputType = $type;
    }else{
      $this->outputType = 'direct';
    }
  }


  public function method_POST() {
    $output = 'DAVACL_Test_Post_Resource::method_POST() called with output as ' . $this->outputType . ' for resource ' . $this->path . "\n";
    switch ( $this->outputType ) {
      case 'string':
        return $output;
      default:
        print( $output );
      return;
    }
  }

}

// End of file