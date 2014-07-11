<?php
/**
 * Contains tests for the DAV_Request_LOCK class
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
 * Contains tests for the DAV_Request_LOCK class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_LOCKTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'LOCK';
    $_SERVER['HTTP_DEPTH'] = '0';
    $_SERVER['REQUEST_URI'] = '/path/to/resource';
    unset( $_SERVER['HTTP_TIMEOUT'] );
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );

    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Test_Lock_Provider', array( 'getlock' ) );
    DAV::$LOCKPROVIDER->expects( $this->any() )
                      ->method( 'getlock' )
                      ->will( $this->returnCallback( function( $path ) {
                        static $times_called = 0;
                        if ( ( $path === $_SERVER['REQUEST_URI'] ) && ( $times_called++ > 0 ) ) {
                          return new DAV_Element_activelock( array(
                              'lockroot' => $_SERVER['REQUEST_URI'],
                              'depth' => '0',
                              'locktoken' => '1234567890',
                              'owner' => 'somebody',
                              'timeout' => 'seconds-1234'
                          ) );
                        }else{
                          return null;
                        }
                      } ) );
  }


  public function testConstructorWrongTimeoutHeader() {
    $_SERVER['HTTP_TIMEOUT'] = 'something wrong';
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_LOCK::inst();
  }


  public function testConstructorEmptyBody() {
    DAV_Test_Request_LOCK::setInputstring( '' );
    $obj = DAV_Test_Request_LOCK::inst();
    $this->assertFalse( $obj->newlock, 'If no request body is specified, DAV_Request_LOCK should indicate the lock to be existing' );
  }


  public function testConstructorWrongXML() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_LOCK::setInputstring( 'something that is not XML' );
    DAV_Test_Request_LOCK::inst();
  }


  public function testConstructorSharedLock() {
    $this->setExpectedException( 'DAV_Status', 'Shared locks are not supported.', DAV::HTTP_NOT_IMPLEMENTED );
    DAV_Test_Request_LOCK::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D='DAV:'>
  <D:lockscope><D:shared/></D:lockscope>
  <D:locktype><D:write/></D:locktype>
  <D:owner>
    <D:href>http://tests.com/</D:href>
  </D:owner>
</D:lockinfo>
EOS
    );
    DAV_Test_Request_LOCK::inst();
  }


  public function testConstructorNoneWriteLock() {
    $this->setExpectedException( 'DAV_Status', null, 422 );
    DAV_Test_Request_LOCK::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D='DAV:'>
  <D:lockscope><D:exclusive/></D:lockscope>
  <D:locktype><D:read/></D:locktype>
  <D:owner>
    <D:href>http://tests.com/</D:href>
  </D:owner>
</D:lockinfo>
EOS
    );
    $obj = DAV_Test_Request_LOCK::inst();
  }


  public function testConstructorExclusiveLock() {
    $_SERVER['HTTP_TIMEOUT'] = 'Infinite, Second-4100000000';
    DAV_Test_Request_LOCK::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D='DAV:'>
  <D:lockscope><D:exclusive/></D:lockscope>
  <D:locktype><D:write/></D:locktype>
  <D:owner>
    <D:href>http://tests.com/</D:href>
  </D:owner>
</D:lockinfo>
EOS
    );
    $obj = DAV_Test_Request_LOCK::inst();
    $this->assertSame( '<D:href>http://tests.com/</D:href>', $obj->owner, 'DAV_Request_LOCK should have the right owner set' );
    $this->assertSame( array( 0, 4100000000 ), $obj->timeout, 'DAV_Request_LOCK should have the correct timeout set' );
    $this->assertTrue( $obj->newlock, 'If a request body is specified, DAV_Request_LOCK should indicate the lock to be new' );

    return $obj;
  }


  public function testDepth() {
    DAV_Test_Request_LOCK::setInputstring('');
    $obj = DAV_Test_Request_LOCK::inst();
    $_SERVER['HTTP_DEPTH'] = '0';
    $this->assertSame( '0', $obj->depth(), 'DAV_Request_LOCK::depth() should return the Depth header correctly' );
    unset( $_SERVER['HTTP_DEPTH'] );
    $this->assertSame( DAV::DEPTH_INF, $obj->depth(), 'DAV_Request_LOCK::depth() should return \'infinity\' if the Depth header is not set' );
  }


  public function testHandleWrongDepth() {
    $_SERVER['HTTP_DEPTH'] = '1';
    DAV_Test_Request_LOCK::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D='DAV:'>
  <D:lockscope><D:exclusive/></D:lockscope>
  <D:locktype><D:write/></D:locktype>
  <D:owner>
    <D:href>http://tests.com/</D:href>
  </D:owner>
</D:lockinfo>
EOS
    );
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
Depth: 1 is not supported for method LOCK.
EOS
    );
    $obj = DAV_Test_Request_LOCK::inst();
    $obj->handleRequest();
  }


  /**
   * @depends  testConstructorExclusiveLock
   * @param  DAV_Test_Request_LOCK  $obj
   */
  public function testHandleCreateButLocked( $obj ) {
    DAV::$LOCKPROVIDER = new DAV_Test_Lock_Provider();
    DAV::$LOCKPROVIDER->returnLock( true );
    $this->expectOutputString( <<<EOS
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 423 Locked
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:no-conflicting-lock><D:href>/path/to/resource</D:href></D:no-conflicting-lock>
</D:error>
EOS
    );
    $obj->handleRequest();
  }


  /**
   * @depends  testConstructorExclusiveLock
   * @param  DAV_Test_Request_LOCK  $obj
   */
  public function testCreateLockUnexistingParent( $obj ) {
    $_SERVER['REQUEST_URI'] = '/unexisting/parent';
    DAV::$REGISTRY->setResourceClass( null );
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 409 Conflict
HTTP/1.1 409 Conflict
Unable to LOCK unexisting parent collection
EOS
    );
    $obj->handleRequest();
  }


  /**
   * @depends  testConstructorExclusiveLock
   * @param  DAV_Test_Request_LOCK  $obj
   */
  public function testCreateLockUnexisting( $obj ) {
    $new_resource = new DAVACL_Test_Resource( $_SERVER['REQUEST_URI'] );
    $collection = $this->getMock( 'DAVACL_Test_Collection', array( 'create_member'), array( dirname( $_SERVER['REQUEST_URI'] ) ) );
    $collection->expects( $this->once() )
               ->method( 'create_member' )
               ->with( $this->equalTo( basename( $_SERVER['REQUEST_URI'] ) ) )
               ->will( $this->returnValue( $new_resource ) );

    $map = array(
        array( $_SERVER['REQUEST_URI'], null ),
        array( dirname( $_SERVER['REQUEST_URI'] ), $collection )
    );
    DAV::$REGISTRY = $this->getMock( 'DAV_Test_Registry', array( 'resource' ) );
    DAV::$REGISTRY->expects( $this->any() )
                  ->method( 'resource' )
                  ->will( $this->returnValueMap( $map ) );

    $this->expectOutputString( <<<EOS
Content-Type: application/xml; charset="utf-8"
Location: http://example.org/path/to/resource
Lock-Token: <urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4>
HTTP/1.1 201 Created
<?xml version="1.0" encoding="utf-8"?>
<D:prop xmlns:D="DAV:"><D:lockdiscovery><D:activelock>
<D:lockscope><D:exclusive/></D:lockscope>
<D:locktype><D:write/></D:locktype>
<D:depth>0</D:depth>
<D:owner>somebody</D:owner>
<D:timeout>Second-0</D:timeout>
<D:lockroot><D:href>/path/to/resource</D:href></D:lockroot>
</D:activelock></D:lockdiscovery></D:prop>
EOS
    );
    $obj->handleRequest();
  }


  /**
   * @depends  testConstructorExclusiveLock
   * @param  DAV_Test_Request_LOCK  $obj
   */
  public function testCreateLockCollection( $obj ) {
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Collection' );

    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/to/resource/
Content-Type: application/xml; charset="utf-8"
Lock-Token: <urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4>
<?xml version="1.0" encoding="utf-8"?>
<D:prop xmlns:D="DAV:"><D:lockdiscovery><D:activelock>
<D:lockscope><D:exclusive/></D:lockscope>
<D:locktype><D:write/></D:locktype>
<D:depth>0</D:depth>
<D:owner>somebody</D:owner>
<D:timeout>Second-0</D:timeout>
<D:lockroot><D:href>/path/to/resource</D:href></D:lockroot>
</D:activelock></D:lockdiscovery></D:prop>
EOS
    );
    $obj->handleRequest();
  }


  public function testRefreshLockWithoutIf() {
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Test_Lock_Provider', array( 'getlock' ) );
    DAV::$LOCKPROVIDER->expects( $this->any() )
                      ->method( 'getlock' )
                      ->will( $this->returnValue( new DAV_Element_activelock( array(
                              'lockroot' => $_SERVER['REQUEST_URI'],
                              'depth' => '0',
                              'locktoken' => '1234567890',
                              'owner' => 'somebody',
                              'timeout' => 'seconds-1234'
                          ) ) ) );
    $this->expectOutputString( <<<EOS
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 400 Bad Request
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:lock-token-submitted><D:href>/path/to/resource</D:href></D:lock-token-submitted>
</D:error>
EOS
    );

    DAV_Test_Request_LOCK::setInputstring( '' );
    $obj = DAV_Test_Request_LOCK::inst();
    $obj->handleRequest();
  }


  public function testRefreshLockWrongLock() {
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Test_Lock_Provider', array( 'getlock' ) );
    DAV::$LOCKPROVIDER->expects( $this->any() )
                      ->method( 'getlock' )
                      ->will( $this->returnValue( new DAV_Element_activelock( array(
                              'lockroot' => $_SERVER['REQUEST_URI'],
                              'depth' => '0',
                              'locktoken' => 'urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4',
                              'owner' => 'somebody',
                              'timeout' => 'seconds-1234'
                          ) ) ) );
    $_SERVER['HTTP_IF'] = '(<urn:uuid:12345678-9012-3456-7890-123456789012>)';
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 412 Precondition Failed
HTTP/1.1 412 Precondition Failed

EOS
    );

    DAV_Test_Request_LOCK::setInputstring( '' );
    $obj = DAV_Test_Request_LOCK::inst();
    $obj->handleRequest();
  }


  public function testRefreshLock() {
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Test_Lock_Provider', array( 'getlock' ) );
    DAV::$LOCKPROVIDER->expects( $this->any() )
                      ->method( 'getlock' )
                      ->will( $this->returnValue( new DAV_Element_activelock( array(
                              'lockroot' => $_SERVER['REQUEST_URI'],
                              'depth' => '0',
                              'locktoken' => 'urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4',
                              'owner' => 'somebody',
                              'timeout' => 'seconds-1234'
                          ) ) ) );
    $_SERVER['HTTP_IF'] = '(<urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4>)';
    $this->expectOutputString( <<<EOS
Content-Type: application/xml; charset="utf-8"
<?xml version="1.0" encoding="utf-8"?>
<D:prop xmlns:D="DAV:"><D:lockdiscovery><D:activelock>
<D:lockscope><D:exclusive/></D:lockscope>
<D:locktype><D:write/></D:locktype>
<D:depth>0</D:depth>
<D:owner>somebody</D:owner>
<D:timeout>Second-0</D:timeout>
<D:locktoken>
<D:href>urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4</D:href>
</D:locktoken>
<D:lockroot><D:href>/path/to/resource</D:href></D:lockroot>
</D:activelock></D:lockdiscovery></D:prop>
EOS
    );

    DAV_Test_Request_LOCK::setInputstring( '' );
    $obj = DAV_Test_Request_LOCK::inst();
    $obj->handleRequest();
  }

} // class DAV_Request_LOCKTest

// End of file