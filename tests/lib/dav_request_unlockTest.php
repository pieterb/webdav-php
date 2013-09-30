<?php
/**
 * Contains tests for the DAV_Request_UNLOCK class
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
 * Contains tests for the DAV_Request_UNLOCK class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_UNLOCKTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAV_Request_UNLOCK  The object we will test
   */
  private $obj;


  public function setUp() {
    DAV::$LOCKPROVIDER = new DAV_Test_Lock_Provider();
    $_SERVER['REQUEST_METHOD'] = 'UNLOCK';
    $_SERVER['HTTP_LOCK_TOKEN'] = '<' . DAV::$LOCKPROVIDER->setlock( null, null, null, null ) . '>';
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $this->obj = DAV_Request::inst();
  }


  public function testConstructorWithoutLockToken() {
    unset( $_SERVER['HTTP_LOCK_TOKEN'] );
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
Missing required Lock-Token: header.
EOS
    );
    DAV_Request::inst();
  }


  public function testConstructor() {
    $this->assertSame( DAV::$LOCKPROVIDER->setlock( null, null, null, null ), $this->obj->locktoken, 'DAV_Request_UNLOCK constructor should parse and store lock tokens' );
  }


  public function testHandleWithoutProvider() {
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 403 Forbidden
HTTP/1.1 403 Forbidden

EOS
    );
    DAV::$LOCKPROVIDER = null;
    $this->obj->handleRequest();
  }


  public function testHandleWrongLock() {
    $_SERVER['HTTP_LOCK_TOKEN'] = '<urn:uuid:aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa>';
    $this->obj = DAV_Request::inst();
    $this->expectOutputString( <<<EOS
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 409 Conflict
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:lock-token-matches-request-uri/>
</D:error>
EOS
    );
    $this->obj->handleRequest();
  }


  public function testHandle() {
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Test_Lock_Provider', array( 'unlock' ) );
    DAV::$LOCKPROVIDER->expects( $this->once() )
                      ->method( 'unlock' )
                      ->with( $this->equalTo( $_SERVER['REQUEST_URI'] ) )
                      ->will( $this->returnValue( true ) );

    $this->expectOutputString( <<<EOS
HTTP/1.1 204 No Content

EOS
    );
    $this->obj->handleRequest();
  }

} // class DAV_Request_LOCKTest

// End of file