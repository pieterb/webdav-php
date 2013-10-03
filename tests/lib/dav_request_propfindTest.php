<?php
/**
 * Contains tests for the DAV_Request_PROPFIND class
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
 * Contains tests for the DAV_Request_PROPFIND class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_PROPFINDTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'PROPFIND';
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    DAV::$LOCKPROVIDER = null;
    DAV_Test_Request_PROPFIND::setInputstring( '' );
  }


  public function testConstructorEmptyBody() {
    DAV_Test_Request_PROPFIND::setInputstring( '' );
    $obj = DAV_Test_Request_PROPFIND::inst();
    $this->assertSame( 'allprop', $obj->requestType, 'DAV_Request_PROPFIND should return allprop when no request body is specified' );
  }


  public function testConstructorWrongXML() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_PROPFIND::setInputstring( 'something that is not XML' );
    DAV_Test_Request_PROPFIND::inst();
  }


  public function testConstructorNoProp() {
    $this->setExpectedException( 'DAV_Status', null, 422 );
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
</propfind>
EOS
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
  }


  public function testConstructorAllpropXML() {
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <allprop/>
  <include xmlns:ns1="http://ns.example.com/tests/">
    <ns1:prop1/>
    <ns1:prop2/>
    <ns1:test3/>
    <ns1:tests4/>
  </include>
</propfind>
EOS
    );
    $expectedProps = array(
        'http://ns.example.com/tests/ prop1',
        'http://ns.example.com/tests/ prop2',
        'http://ns.example.com/tests/ test3',
        'http://ns.example.com/tests/ tests4'
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    $this->assertSame( 'allprop'     , $obj->requestType, 'DAV_Request_PROPFIND should have allprop as requestType when appropriate' );
    $this->assertSame( $expectedProps, $obj->props      , 'DAV_Request_PROPFIND should have correct properties set with a \'allprop\' request with include tag' );
  }


  public function testConstructorPropnameXML() {
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <propname/>
</propfind>
EOS
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    $this->assertSame( 'propname', $obj->requestType, 'DAV_Request_PROPFIND should have propname as requestType when appropriate' );
  }


  public function testConstructorPropXML() {
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <prop xmlns:ns1="http://ns.example.com/tests/">
    <ns1:prop1/>
    <ns1:prop2/>
    <ns1:test3/>
    <ns1:tests4/>
  </prop>
</propfind>
EOS
    );
    $expectedProps = array(
        'http://ns.example.com/tests/ prop1',
        'http://ns.example.com/tests/ prop2',
        'http://ns.example.com/tests/ test3',
        'http://ns.example.com/tests/ tests4'
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    $this->assertSame( 'prop'        , $obj->requestType, 'DAV_Request_PROPFIND should have prop as requestType when appropriate' );
    $this->assertSame( $expectedProps, $obj->props      , 'DAV_Request_PROPFIND should have correct properties set with a \'prop\' request' );
  }


  public function testDepth() {
    $_SERVER['HTTP_DEPTH'] = '0';
    $obj = DAV_Test_Request_PROPFIND::inst();
    $this->assertSame( '0', $obj->depth(), 'DAV_Request_PROPFIND::depth() should return the Depth header correctly' );
    unset( $_SERVER['HTTP_DEPTH'] );
    $this->assertSame( DAV::DEPTH_INF, $obj->depth(), 'DAV_Request_PROPFIND::depth() should return \'infinity\' if the Depth header is not set' );
  }


  public function testHandleDepthInfinity() {
    $obj = DAV_Test_Request_PROPFIND::inst();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Collection' );
    $_SERVER['HTTP_DEPTH'] = 'infinity';
    $this->expectOutputString( <<<EOS
Content-Location: http://example.org/path/
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 403 Forbidden
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:propfind-finite-depth/>
</D:error>
EOS
    );
    $obj->handleRequest();
  }


  public function testHandlePropName() {
    $_SERVER['REQUEST_URI'] = '/some_collection/';
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <propname/>
</propfind>
EOS
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Collection' );
    $_SERVER['HTTP_DEPTH'] = '1';
    $expectedOutput = <<<EOS
Content-Type: application/xml; charset="utf-8"
HTTP/1.1 207 Multi-Status
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
<D:response><D:href>/some_collection/</D:href>
<D:propstat><D:prop>
<D:resourcetype/>
<D:supported-report-set/>
<D:owner/>
<D:group/>
<D:supported-privilege-set/>
<D:current-user-privilege-set/>
<D:acl/>
<D:acl-restrictions/>
<D:inherited-acl-set/>
<D:principal-collection-set/>
<D:current-user-principal/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
<D:response><D:href>/some_collection/child1</D:href>
<D:propstat><D:prop>
<D:resourcetype/>
<D:supported-report-set/>
<D:owner/>
<D:group/>
<D:supported-privilege-set/>
<D:current-user-privilege-set/>
<D:acl/>
<D:acl-restrictions/>
<D:inherited-acl-set/>
<D:principal-collection-set/>
<D:current-user-principal/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
<D:response><D:href>/some_collection/child2</D:href>
<D:propstat><D:prop>
<D:resourcetype/>
<D:supported-report-set/>
<D:owner/>
<D:group/>
<D:supported-privilege-set/>
<D:current-user-privilege-set/>
<D:acl/>
<D:acl-restrictions/>
<D:inherited-acl-set/>
<D:principal-collection-set/>
<D:current-user-principal/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
</D:multistatus>
EOS
    ;
    $this->expectOutputString( $expectedOutput );
    $obj->handleRequest();
  }


  public function testHandleAllProp() {
    $_SERVER['REQUEST_URI'] = '/some_collection/';
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <allprop/>
</propfind>
EOS
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $expectedOutput = <<<EOS

<D:response><D:href>/some_collection/</D:href>
<D:propstat><D:prop>
<D:supported-report-set><D:supported-report><D:expand-property/></D:supported-report></D:supported-report-set>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
EOS
    ;
    $this->expectOutputString( $expectedOutput );
    $obj->handleRequest();
  }


  public function testHandleProps() {
    $_SERVER['REQUEST_URI'] = '/some_collection/';
    DAV_Test_Request_PROPFIND::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<propfind xmlns="DAV:">
  <prop xmlns:ns1="http://ns.example.com/tests/">
    <ns1:prop1/>
    <ns1:prop2/>
    <ns1:test3/>
    <ns1:tests4/>
  </prop>
</propfind>
EOS
    );
    $obj = DAV_Test_Request_PROPFIND::inst();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $expectedOutput = <<<EOS

<D:response><D:href>/some_collection/</D:href>
<D:propstat><D:prop>
<ns:prop1 xmlns:ns="http://ns.example.com/tests/"/>
<ns:prop2 xmlns:ns="http://ns.example.com/tests/"/>
<ns:test3 xmlns:ns="http://ns.example.com/tests/"/>
<ns:tests4 xmlns:ns="http://ns.example.com/tests/"/>
</D:prop>
<D:status>HTTP/1.1 404 Not Found</D:status>
</D:propstat>
</D:response>
EOS
    ;
    $this->expectOutputString( $expectedOutput );
    $obj->handleRequest();
  }


} // class DAV_Request_PROPFINDTest


class DAV_Test_Request_PROPFIND extends DAV_Request_PROPFIND {

  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }

  
  private static $inputstring = '';


  public static function setInputstring( $inputstring ) {
    self::$inputstring = $inputstring;
  }


  protected static function inputstring() {
    return self::$inputstring;
  }

} // Class DAV_Test_Request_PROPFIND

// End of file