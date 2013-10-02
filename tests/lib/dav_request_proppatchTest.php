<?php
/**
 * Contains tests for the DAV_Request_PROPPATCH class
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
 * Contains tests for the DAV_Request_PROPPATCH class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_PROPPATCHTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'PROPPATCH';
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
  }


  public function testConstructorEmptyBody() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_PROPPATCH::setInputstring( '' );
    DAV_Test_Request_PROPPATCH::inst();
  }


  public function testConstructorWrongXML() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_PROPPATCH::setInputstring( 'something that is not XML' );
    DAV_Test_Request_PROPPATCH::inst();
  }


  public function testConstructor() {
    DAV_Test_Request_PROPPATCH::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:propertyupdate xmlns:D="DAV:" xmlns:ns1="http://ns.example.com/tests/">
  <D:set>
    <D:prop>
      <ns1:prop1>This is a test property</ns1:prop1>
    </D:prop>
  </D:set>
  <D:remove>
    <D:prop><ns1:old-property/></D:prop>
  </D:remove>
</D:propertyupdate>
EOS
    );
    $obj = DAV_Test_Request_PROPPATCH::inst();
    $expected = array (
      'http://ns.example.com/tests/ prop1' => 'This is a test property',
      'http://ns.example.com/tests/ old-property' => null
    );
    $this->assertSame( $expected, $obj->props, 'DAV_Request_PROPPATCH should have its \'props\' attribute set correctly' );
  }


  public function testHandleNoProps() {
    DAV_Test_Request_PROPPATCH::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:propertyupdate xmlns:D="DAV:" xmlns:ns1="http://ns.example.com/tests/">
</D:propertyupdate>
EOS
    );
    $this->expectOutputString( <<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
No properties found in request body.
EOS
    );
    $obj = DAV_Test_Request_PROPPATCH::inst();
    $obj->handleRequest();
  }


  public function testHandle() {
    DAV_Test_Request_PROPPATCH::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:propertyupdate xmlns:D="DAV:" xmlns:ns1="http://ns.example.com/tests/">
  <D:set>
    <D:prop>
      <ns1:prop1>Some value for prop1</ns1:prop1>
    </D:prop>
  </D:set>
  <D:remove>
    <D:prop><ns1:old-property/></D:prop>
  </D:remove>
</D:propertyupdate>
EOS
    );
    $this->expectOutputString( <<<EOS
DAVACL_Test_Resource::method_PROPPATCH() called for /path and parameters http://ns.example.com/tests/ prop1 - 'Some value for prop1'
DAVACL_Test_Resource::method_PROPPATCH() called for /path and parameters http://ns.example.com/tests/ old-property - NULL
DAVACL_Test_Resource::storeProperties() called for /path

<D:response><D:href>/path</D:href>
<D:propstat><D:prop>
<ns:prop1 xmlns:ns="http://ns.example.com/tests/"/>
<ns:old-property xmlns:ns="http://ns.example.com/tests/"/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
EOS
    );
    $obj = DAV_Test_Request_PROPPATCH::inst();
    $obj->handleRequest();
  }


  public function testHandleForbiddenProps() {
    DAV_Test_Request_PROPPATCH::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:propertyupdate xmlns:D="DAV:" xmlns:ns1="http://ns.example.com/tests/">
  <D:set>
    <D:prop>
      <ns1:prop1>Some value for prop1</ns1:prop1>
      <D:creationdate>some date</D:creationdate>
      <forbidden_to_write xmlns="test:">some date</forbidden_to_write>
    </D:prop>
  </D:set>
</D:propertyupdate>
EOS
    );
    $this->expectOutputString( <<<EOS
DAVACL_Test_Resource::method_PROPPATCH() called for /path and parameters http://ns.example.com/tests/ prop1 - 'Some value for prop1'

<D:response><D:href>/path</D:href>
<D:propstat><D:prop>
<D:creationdate/>
<ns:forbidden_to_write xmlns:ns="test:"/>
</D:prop>
<D:status>HTTP/1.1 403 Forbidden</D:status>
</D:propstat>
<D:propstat><D:prop>
<ns:prop1 xmlns:ns="http://ns.example.com/tests/"/>
</D:prop>
<D:status>HTTP/1.1 424 Failed Dependency</D:status>
</D:propstat>
</D:response>
EOS
    );
    $obj = DAV_Test_Request_PROPPATCH::inst();
    $obj->handleRequest();
  }

} // class DAV_Request_PROPPATCHTest


class DAV_Test_Request_PROPPATCH extends DAV_Request_PROPPATCH {

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

} // Class DAV_Test_Request_PROPPATCH

// End of file