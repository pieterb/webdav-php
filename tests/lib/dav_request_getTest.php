<?php
/**
 * Contains tests for the DAV_Request_GET class
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
 * Contains tests for the DAV_Request_GET class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_GETTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_GET  The object we will test
   */
  private $obj;


  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $this->obj = DAV_Request::inst();
  }


  public function testHandleResourceWritesDirectly() {
    $resource = new DAVACL_Test_Get_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'direct' );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
DAVACL_Test_Get_Resource::method_GET() called with output as direct for resource /path
Content-Type: application/octet-stream
Accept-Ranges: bytes
Content-Length: 87

EOS
    ); // Note that the headers are behind the body. This is because in test modus, headers will be printed and thus appear in the output buffer behind the body.
    $this->obj->handleRequest();
  }


  public function testHandleResourceReturnsString() {
    $resource = new DAVACL_Test_Get_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'string' );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
Content-Type: application/octet-stream
Accept-Ranges: bytes
Content-Length: 87
DAVACL_Test_Get_Resource::method_GET() called with output as string for resource /path

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleResourceReturnsStream() {
    $resource = new DAVACL_Test_Get_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'stream' );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
Content-Type: application/octet-stream
Accept-Ranges: bytes
DAVACL_Test_Get_Resource::method_GET() called with output as stream for resource /path

EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleRange() {
    $resource = new DAVACL_Test_Get_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'stream' );
    $_SERVER['HTTP_RANGE'] = 'bytes=3-10';

    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
Content-Type: application/octet-stream
Accept-Ranges: bytes
Content-Length: 8
Content-Range: bytes 3-10/87
HTTP/1.1 206 Partial Content
ACL_Test
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandleMultipleRanges() {
    $resource = new DAVACL_Test_Get_Resource( $_SERVER['REQUEST_URI'] );
    $resource->setOutputType( 'stream' );
    $_SERVER['HTTP_RANGE'] = 'bytes=3-10,20-25';

    DAV::$REGISTRY->setResourceClass( $resource );
    $expected = <<<EOS
Accept-Ranges: bytes
Content-Type: multipart/byteranges; boundary=%%ID%%
HTTP/1.1 206 Partial Content
This is a message in multipart MIME format.

--%%ID%%
Content-Type: application/octet-stream
Content-Range: 3-10/87
Content-Length: 8

ACL_Test
--%%ID%%
Content-Type: application/octet-stream
Content-Range: 20-25/87
Content-Length: 6

urce::
--%%ID%%--

EOS
    ;

    ob_start();
    $this->obj->handleRequest();
    $output = ob_get_clean();
    $matches = array();
    preg_match( '#^Content\-Type: multipart/byteranges; boundary=(.*)$#m' , $output, $matches );
    $expected = str_replace( array( '%%ID%%', "\r"), array( $matches[1], '' ), $expected );
    $output = str_replace( "\r", '', $output );
    $this->assertSame( $expected, $output, "DAV_Request_GET::handle() should return correctly formed multipart output when multiple ranges are requested");
  }


  public function testRange_header() {
    $_SERVER['HTTP_RANGE'] = 'bytes=3-10';
    $this->assertSame( array( array( 'start' => 3, 'end' => 10 ) ), DAV_Request_GET::range_header( 12345 ), 'DAV_Request_GET::range_header() should parse a simple Range header correctly' );
    $_SERVER['HTTP_RANGE'] = 'bytes=3-10,20-25';
    $this->assertSame( array( array( 'start' => 3, 'end' => 10 ), array( 'start' => 20, 'end' => 25 ) ), DAV_Request_GET::range_header( 12345 ), 'DAV_Request_GET::range_header() should parse a complex Range header correctly' );
  }


  public function testRange_headerWrongRequest() {
    $this->setExpectedException( 'DAV_Status', '', 400 );
    $_SERVER['HTTP_RANGE'] = 'chars=3-10';
    DAV_Request_GET::range_header( 12345 );
  }


  public function testRange_headerTooSmallResource() {
    $this->setExpectedException( 'DAV_Status', '', 416 );
    $_SERVER['HTTP_RANGE'] = 'bytes=-10';
    DAV_Request_GET::range_header( 7 );
  }

} // Class DAV_Request_GET


class DAVACL_Test_Get_Resource extends DAVACL_Test_Resource {

  private $outputType = 'stream';


  public function setOutputType( $type ) {
    if ( in_array( $type, array( 'direct', 'string' ) ) ) {
      $this->outputType = $type;
    }else{
      $this->outputType = 'stream';
    }
  }


  public function method_GET() {
    $output = 'DAVACL_Test_Get_Resource::method_GET() called with output as ' . $this->outputType . ' for resource ' . $this->path . "\n";
    switch ( $this->outputType ) {
      case 'direct':
        print( $output );
        return;
      case 'string':
        return $output;
      default:
        $fp = fopen( 'php://temp/GET_body', 'r+' );
        fwrite( $fp, $output );
        rewind( $fp );
      return $fp;
    }
  }

}

// End of file