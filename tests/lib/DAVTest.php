<?php
/**
 * Contains tests for the DAV class
 * 
 * Copyright ©2013-2014 SURFsara b.v., Amsterdam, The Netherlands
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
 * Contains tests for the DAV class
 * @package DAV
 * @subpackage tests
 */
class DAVTest extends PHPUnit_Framework_TestCase {
  
  /**
   * Set up the $_SERVER superglobal to contain all elements required by the DAV class
   */
  protected function setUp() {
    $_SERVER['HTTP_USER_AGENT'] = '';
    $_SERVER['HTTPS'] = true;
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['SERVER_NAME'] = 'webdav.org';
    $_SERVER['SERVER_PORT'] = 443;
  }


  public function testDetermine_client() {
    $this->assertSame( DAV::CLIENT_UNKNOWN          , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_UNKNOWN with empty user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)';
    $this->assertSame( DAV::CLIENT_IE_OLD           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE_OLD with IE 7 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; .NET CLR 2.7.58687; SLCC2; Media Center PC 5.0; Zune 3.4; Tablet PC 3.6; InfoPath.3)';
    $this->assertSame( DAV::CLIENT_IE8              , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE8 with IE 8 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/13.0.782.215)';
    $this->assertSame( DAV::CLIENT_IE9              , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE9 with IE 9 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';
    $this->assertSame( DAV::CLIENT_IE10             , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE10 with IE 10 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.17 Safari/537.36';
    $this->assertSame( DAV::CLIENT_CHROME           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_CHROME with Chrome user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:23.0) Gecko/20100101 Firefox/23.0';
    $this->assertSame( DAV::CLIENT_FIREFOX          , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_FIREFOX with Firefox user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25';
    $this->assertSame( DAV::CLIENT_SAFARI           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_SAFARI with Safari user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'gvfs/1.6.1';
    $this->assertSame( DAV::CLIENT_GVFS             , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_GVFS with gvfs user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Microsoft Data Access Internet Publishing Provider DAV';
    $this->assertSame( DAV::CLIENT_WINDOWS_WEBFOLDER, DAV::determine_client(), 'DAV::determine_client() should return CLIENT_WINDOWS_WEBFOLDER with windows webfolder user_agent string' );
    $this->assertTrue( ( DAV::CLIENT_IE & DAV::CLIENT_IE_OLD & DAV::CLIENT_IE8 & DAV::CLIENT_IE9 & DAV::CLIENT_IE10 ) === DAV::CLIENT_IE , DAV::determine_client(), 'All DAV::CLIENT_IE* constants should have the DAV::CLIENT_IE bit set' );
  }


  public function testExpand() {
    $this->assertSame( 'D:displayname'                      , DAV::expand( 'DAV: displayname' )           , 'DAV::expand() should return correct value when called with property in the DAV namespace' );
    $this->assertSame( 'testproperty xmlns="tests://tests/"', DAV::expand( 'tests://tests/ testproperty' ), 'DAV::expand() should return correct value when called with property in custom namespace' );
  }


  public function testForbidden() {
    $status = DAV::forbidden( 'Test message' );
    $this->assertInstanceOf( 'DAV_Status', $status              , 'DAV::forbidden() should return a DAV_Status object' );
    $this->assertSame( 403             , $status->getCode()   , 'DAV::forbidden() should return a DAV_Status object with code 403' );
    $this->assertSame( 'Test message'  , $status->getMessage(), 'DAV::forbidden() should return a DAV_Status object with code 403' );
  }
  
  
  public function testConfig() {
    $this->assertSame( array(), DAV::getConfig(), 'DAV::getConfig() should return an empty array before setting any configuration options' );
    DAV::setDebugFile( '/tmp/debug.txt' );
    $this->assertSame( array( 'debug' => array( 'file' => '/tmp/debug.txt' ) ), DAV::getConfig(), 'DAV::getConfig() should contain a 2 dimensional array containing the path to the de bug file' );
  }
  
  
  public function testGetPath() {
    $this->assertSame( DAV::parseURI( $_SERVER['REQUEST_URI'], true ), DAV::getPath(), 'DAV::getPath() should return the correct path' );
  }
  
  
  public function testGetSupported_Properties() {
    $value = DAV::getSupported_Properties();
    $expectedResult = array_merge(
      DAV::$WEBDAV_PROPERTIES,
      DAV::$PRINCIPAL_PROPERTIES,
      DAV::$ACL_PROPERTIES
    );
    $this->assertSame( $expectedResult, $value );
  }


  public function testAddSupported_Properties() {
    $expectedResult = array_merge(
      DAV::$WEBDAV_PROPERTIES,
      DAV::$PRINCIPAL_PROPERTIES,
      DAV::$ACL_PROPERTIES
    );
    $expectedResult['namespace property'] = 'name';

    $this->assertSame( $expectedResult, DAV::addSupported_Properties( 'namespace property', 'name' ) );
  }


  public function testHeader() {
    ob_start();
    DAV::header( array( 
        'status' => DAV::HTTP_EXPECTATION_FAILED,
        'x-test-header' => 'with a test value'
    ) );
    $returnedValue = ob_get_contents();
    ob_end_clean();
    $this->assertSame( "x-test-header: with a test value\nHTTP/1.1 417 Expectation Failed\n", $returnedValue, 'DAV::header() should print the correct headers (print them, not sent them, because we\'re in testing mode' );
  }


  public function testHttpDate() {
    $this->assertSame( 'Sat, 12 Jan 1985 12:34:56 GMT', DAV::httpDate( 474381296 ), 'DAV::httpDate() should return the right date string' );
  }


  public function testIsValidURI() {
    $this->assertTrue ( DAV::isValidURI( 'http://webdav.org/some/path/to/a/file.txt' ), 'DAV:isValidUri() should return true for a valid uri' );
    $this->assertFalse( DAV::isValidURI( '@#$wrong_uri/but/with/path/t43#$' )         , 'DAV:isValidUri() should return false for an invalid uri' );
  }

  /**
   * @expectedException PHPUnit_Framework_Error_Warning
   */
  public function testParseURI() {
    $this->assertSame( DAV::parseURI( 'https://webdav.org/some/path/to/a/file.txt' ), '/some/path/to/a/file.txt', 'DAV::parseURI() should return the correct path for a regular uri' );
    try{
      DAV::parseURI( 'https://non-webdav.org/some/path/to/a/file.txt' );
      $this->assertTrue( false, 'DAV::parseURI() should throw an DAV_Status exception when the uri is out of scope of this server' );
    } catch ( DAV_Status $exception) {
      $this->assertSame( 400, $exception->getCode(), 'DAV::parseURI() should throw an DAV_Status exception with code 400 when the uri is out of scope of this server' );
    }
    try{
      $this->assertSame( '/some/path/to/a/file.txt', DAV::parseURI( 'https://non-webdav.org/some/path/to/a/file.txt', false ), 'DAV::parseURI() should return the correct path when it is allowed to have the uri out of scope of this server' );
    } catch ( DAV_Status $exception) {
      $this->assertTrue( false, 'DAV::parseURI() should not throw an DAV_Status exception when it is allowed to have the uri out of scope of this server' );
    }
    $_SERVER['PHP_AUTH_USER'] = 'niek';
    $this->assertSame( DAV::parseURI( 'https://niek@webdav.org/some/path/to/a/file.txt' ), '/some/path/to/a/file.txt', 'DAV::parseURI() should return the correct path for an uri with username' );
  }


  public function testPath2uri() {
    $this->assertSame( 'https://webdav.org/absolute/path', DAV::path2uri( '/absolute/path' ), 'DAV::path2uri() should return correct uri with absolute path' );
    $this->assertSame( 'https://webdav.org/', DAV::path2uri( '/' ), 'DAV::path2uri() should return correct uri with root path' );
    $_SERVER['REQUEST_URI'] = '/requested/path';
    $this->assertSame( 'https://webdav.org/requested/path/relative/path', DAV::path2uri( 'relative/path' ), 'DAV::path2uri() should return correct uri with relative path' );
  }


  public function testRecursiveSerialize() {
    $ns1 = 'tests://test/';
    $ns2 = 'tests://test_more/';
    $xmlDoc = new DOMDocument( '1.0', 'UTF-8' );
    $root = $xmlDoc->createElementNS( $ns1, 'root' );
    $sub1 = $xmlDoc->createElementNS( $ns1, 'sub1' );
    $sub1_1 = $xmlDoc->createElementNS( $ns1, 'sub1_1' );
    $cdata1_1 = $xmlDoc->createCDATASection( 'test text' );
    $sub2 = $xmlDoc->createElementNS( $ns2, 'sub2' );
    $sub2_1 = $xmlDoc->createElementNS( $ns1, 'sub2_1' );
    $comment2_1 = $xmlDoc->createComment( 'test comment' );
    
    $xmlDoc->appendChild( $root );
    $root->appendChild( $sub1 );
    $sub1->appendChild( $sub1_1 );
    $sub1_1->appendChild( $cdata1_1 );
    $root->appendChild( $sub2 );
    $sub2->appendChild( $sub2_1 );
    $sub2_1->appendChild( $comment2_1 );
    
    // Because recursiveSerialize is not forced to use ns1 and ns2 as namespace prefixes, this test is a bit awkward. But it works for now, so let's just ignore it :)
    $this->assertSame( '<ns1:root xmlns:ns1="tests://test/" xmlns:ns2="tests://test_more/"><ns1:sub1><ns1:sub1_1>test text</ns1:sub1_1></ns1:sub1><ns2:sub2><ns1:sub2_1><!--test comment--></ns1:sub2_1></ns2:sub2></ns1:root>', DAV::recursiveSerialize( $root ), 'DAV::recursiveSerialize() should return correct XML' );
  }
  
  
  public function testRedirect() {
    $this->expectOutputString(<<<EOS
Content-Type: text/plain; charset=US-ASCII
Location: http://example.org/some/new/path
HTTP/1.1 301 Moved Permanently
http://example.org/some/new/path
EOS
    );
    DAV::redirect( 301, 'http://example.org/some/new/path' );
  }


  public function testSlashify() {
    $this->assertSame( '/something/with/a/slash/at/the/end/' , DAV::slashify( '/something/with/a/slash/at/the/end/' ), 'DAV::slashify() should not do anything to a string which ends with a slash' );
    $this->assertSame( '/something/with/no/slash/at/the/end/', DAV::slashify( '/something/with/no/slash/at/the/end' ), 'DAV::slashify() should add a slash to a string which doesn\'t end with a slash' );
  }


  public function testStatus_code() {
    $this->assertSame( '207 Multi-Status'        , DAV::status_code( DAV::HTTP_MULTI_STATUS )        , 'DAV::status_code() should return \'207 Multi-Status\' with a 207 parameter' );
    $this->assertSame( '414 Request-URI Too Long', DAV::status_code( DAV::HTTP_REQUEST_URI_TOO_LONG ), 'DAV::status_code() should return \'414 Request-URI Too Long\' with a 414 parameter' );
  }


  public function testUnslashify() {
    $this->assertSame( '/something/with/a/slash/at/the/end' , DAV::unslashify( '/something/with/a/slash/at/the/end/' ), 'DAV::slashify() should remove the trailing slash from a string which ends with a slash' );
    $this->assertSame( '/something/with/no/slash/at/the/end', DAV::unslashify( '/something/with/no/slash/at/the/end' ), 'DAV::slashify() should not do anything to a string which doesn\'t end with a slash' );
  }


  public function testUrlbase() {
    $this->assertSame( 'https://webdav.org'     , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTPS url' );
    $_SERVER['SERVER_PORT'] = 8443;
    $this->assertSame( 'https://webdav.org:8443', DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url with alternate port' );
    $_SERVER['HTTPS'] = null;
    $_SERVER['SERVER_PORT'] = 80;
    $this->assertSame( 'http://webdav.org'      , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url' );
    $_SERVER['SERVER_PORT'] = 8080;
    $this->assertSame( 'http://webdav.org:8080' , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url with alternate port' );
  }
  
  
  public function testVar_dump() {
    // Extensive testing of this function would mostly mean extensive testing of PHP's native var_dump. So that's useless. Let's keep it easy
    $testvar = array( 'element 1', 'element2' );
    $this->assertSame( "array(2) {\n  [0]=>\n  string(9) \"element 1\"\n  [1]=>\n  string(8) \"element2\"\n}\n",
                         DAV::var_dump( $testvar ),
                         'DAV::var_dump() should return the correct value for $testvar' );
  }


  public function testXml_header() {
    $this->assertSame( '<?xml version="1.0" encoding="utf-8"?>' . "\n", DAV::xml_header(), 'DAV::xml_header() should return the correct value' );
  }


  /**
   * @return  string  An XML encoded string
   */
  public function testXmlescape() {
    $returnValue = DAV::xmlescape( '&"\'<>' );
    $this->assertRegExp( '/&amp;&quot;&(apos|#039);&lt;&gt;/', $returnValue, 'DAV::xmlescape() should return escaped characters' );
    return $returnValue;
  }


  /**
   * @depends testXmlescape
   */
  public function testXmlunescape( $encodedString ) {
    $this->assertSame( '&"\'<>', DAV::xmlunescape( $encodedString ), 'DAV::xmlunescape() should return regular characters' );
  }


} // End of DAVTest

// End of file
