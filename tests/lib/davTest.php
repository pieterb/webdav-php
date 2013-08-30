<?php
/**
 * Contains tests for the DAV class
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
 * Contains tests for the DAV class
 * @package DAV
 * @subpackage tests
 */
class DAVTest extends PHPUnit_Framework_TestCase {
  
  /**
   * Set up the $_SERVER superglobal to contain all elements required by the DAV class
   */
  protected function setUp() {
    $_SERVER = array();
    $_SERVER['HTTP_USER_AGENT'] = '';
    $_SERVER['HTTPS'] = true;
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['SERVER_NAME'] = 'webdav.org';
    $_SERVER['SERVER_PORT'] = 443;
    $_SERVER['SERVER_PROTOCOL'] = 'https';
  }


  public function testDetermine_client() {
    $this->assertEquals( DAV::CLIENT_UNKNOWN          , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_UNKNOWN with empty user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)';
    $this->assertEquals( DAV::CLIENT_IE_OLD           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE_OLD with IE 7 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; .NET CLR 2.7.58687; SLCC2; Media Center PC 5.0; Zune 3.4; Tablet PC 3.6; InfoPath.3)';
    $this->assertEquals( DAV::CLIENT_IE8              , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE8 with IE 8 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/13.0.782.215)';
    $this->assertEquals( DAV::CLIENT_IE9              , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE9 with IE 9 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';
    $this->assertEquals( DAV::CLIENT_IE10             , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_IE10 with IE 10 user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.17 Safari/537.36';
    $this->assertEquals( DAV::CLIENT_CHROME           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_CHROME with Chrome user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:23.0) Gecko/20100101 Firefox/23.0';
    $this->assertEquals( DAV::CLIENT_FIREFOX          , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_FIREFOX with Firefox user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25';
    $this->assertEquals( DAV::CLIENT_SAFARI           , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_SAFARI with Safari user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'gvfs/1.6.1';
    $this->assertEquals( DAV::CLIENT_GVFS             , DAV::determine_client(), 'DAV::determine_client() should return CLIENT_GVFS with gvfs user_agent string' );
    $_SERVER['HTTP_USER_AGENT'] = 'Microsoft Data Access Internet Publishing Provider DAV';
    $this->assertEquals( DAV::CLIENT_WINDOWS_WEBFOLDER, DAV::determine_client(), 'DAV::determine_client() should return CLIENT_WINDOWS_WEBFOLDER with windows webfolder user_agent string' );
    $this->assertTrue( ( DAV::CLIENT_IE & DAV::CLIENT_IE_OLD & DAV::CLIENT_IE8 & DAV::CLIENT_IE9 & DAV::CLIENT_IE10 ) === DAV::CLIENT_IE , DAV::determine_client(), 'All DAV::CLIENT_IE* constants should have the DAV::CLIENT_IE bit set' );
  }


  public function testExpand() {
    $this->assertEquals( 'D:displayname'                      , DAV::expand( 'DAV: displayname' )           , 'DAV::expand() should return correct value when called with property in the DAV namespace' );
    $this->assertEquals( 'testproperty xmlns="tests://tests/"', DAV::expand( 'tests://tests/ testproperty' ), 'DAV::expand() should return correct value when called with property in custom namespace' );
  }


  public function testForbidden() {
    $status = DAV::forbidden( 'Test message' );
    $this->assertInstanceOf( 'DAV_Status', $status              , 'DAV::forbidden() should return a DAV_Status object' );
    $this->assertEquals( 403             , $status->getCode()   , 'DAV::forbidden() should return a DAV_Status object with code 403' );
    $this->assertEquals( 'Test message'  , $status->getMessage(), 'DAV::forbidden() should return a DAV_Status object with code 403' );
  }


  public function testHttpDate() {
    $this->assertEquals( 'Sat, 12 Jan 1985 12:34:56 GMT', DAV::httpDate( 474381296 ), 'DAV::httpDate() should return the right date string' );
  }


  public function testIsValidURI() {
    $this->assertTrue ( DAV::isValidURI( 'http://webdav.org/some/path/to/a/file.txt' ), 'DAV:isValidUri() should return true for a valid uri' );
    $this->assertFalse( DAV::isValidURI( '@#$wrong_uri/but/with/path/t43#$' )         , 'DAV:isValidUri() should return false for an invalid uri' );
  }

  /**
   * @expectedException PHPUnit_Framework_Error_Warning
   */
  public function testParseURI() {
    $this->assertEquals( DAV::parseURI( 'https://webdav.org/some/path/to/a/file.txt' ), '/some/path/to/a/file.txt', 'DAV::parseURI() should return the correct path for a regular uri' );
    try{
      DAV::parseURI( 'https://non-webdav.org/some/path/to/a/file.txt' );
      $this->assertTrue( false, 'DAV::parseURI() should throw an DAV_Status exception when the uri is out of scope of this server' );
    } catch ( DAV_Status $exception) {
      $this->assertEquals( 400, $exception->getCode(), 'DAV::parseURI() should throw an DAV_Status exception with code 400 when the uri is out of scope of this server' );
    }
    try{
      $this->assertEquals( '/some/path/to/a/file.txt', DAV::parseURI( 'https://non-webdav.org/some/path/to/a/file.txt', false ), 'DAV::parseURI() should return the correct path when it is allowed to have the uri out of scope of this server' );
    } catch ( DAV_Status $exception) {
      $this->assertTrue( false, 'DAV::parseURI() should not throw an DAV_Status exception when it is allowed to have the uri out of scope of this server' );
    }
    $_SERVER['PHP_AUTH_USER'] = 'niek';
    $this->assertEquals( DAV::parseURI( 'https://niek@webdav.org/some/path/to/a/file.txt' ), '/some/path/to/a/file.txt', 'DAV::parseURI() should return the correct path for an uri with username' );
  }


  public function testPath2uri() {
    $this->assertEquals( 'https://webdav.org/absolute/path', DAV::path2uri( '/absolute/path' ), 'DAV::path2uri() should return correct uri with absolute path' );
    $this->assertEquals( 'https://webdav.org/', DAV::path2uri( '/' ), 'DAV::path2uri() should return correct uri with root path' );
    $_SERVER['REQUEST_URI'] = '/requested/path';
    $this->assertEquals( 'https://webdav.org/requested/path/relative/path', DAV::path2uri( 'relative/path' ), 'DAV::path2uri() should return correct uri with relative path' );
  }
//
//
//  public function testRecursiveSerialize() {
//  }


  public function testSlashify() {
    $this->assertEquals( '/something/with/a/slash/at/the/end/' , DAV::slashify( '/something/with/a/slash/at/the/end/' ), 'DAV::slashify() should not do anything to a string which ends with a slash' );
    $this->assertEquals( '/something/with/no/slash/at/the/end/', DAV::slashify( '/something/with/no/slash/at/the/end' ), 'DAV::slashify() should add a slash to a string which doesn\'t end with a slash' );
  }


  public function testStatus_code() {
    $this->assertEquals( '207 Multi-Status'        , DAV::status_code( DAV::HTTP_MULTI_STATUS )        , 'DAV::status_code() should return \'207 Multi-Status\' with a 207 parameter' );
    $this->assertEquals( '414 Request-URI Too Long', DAV::status_code( DAV::HTTP_REQUEST_URI_TOO_LONG ), 'DAV::status_code() should return \'414 Request-URI Too Long\' with a 414 parameter' );
  }


  public function testUnslashify() {
    $this->assertEquals( '/something/with/a/slash/at/the/end' , DAV::unslashify( '/something/with/a/slash/at/the/end/' ), 'DAV::slashify() should remove the trailing slash from a string which ends with a slash' );
    $this->assertEquals( '/something/with/no/slash/at/the/end', DAV::unslashify( '/something/with/no/slash/at/the/end' ), 'DAV::slashify() should not do anything to a string which doesn\'t end with a slash' );
  }


  public function testUrlbase() {
    $this->assertEquals( 'https://webdav.org'     , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTPS url' );
    $_SERVER['SERVER_PORT'] = 8443;
    $this->assertEquals( 'https://webdav.org:8443', DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url with alternate port' );
    $_SERVER['HTTPS'] = null;
    $_SERVER['SERVER_PORT'] = 80;
    $this->assertEquals( 'http://webdav.org'      , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url' );
    $_SERVER['SERVER_PORT'] = 8080;
    $this->assertEquals( 'http://webdav.org:8080' , DAV::urlbase(), 'DAV::urlbase() should return the correct HTTP url with alternate port' );
  }
  
  
  public function testVar_dump() {
    // Extensive testing of this function would mostly mean extensive testing of PHP's native var_dump. So that's useless. Let's keep it easy
    $testvar = array( 'element 1', 'element2' );
    $this->assertEquals( "array(2) {\n  [0]=>\n  string(9) \"element 1\"\n  [1]=>\n  string(8) \"element2\"\n}\n",
                         DAV::var_dump( $testvar ),
                         'DAV::var_dump() should return the correct value for $testvar' );
  }


  public function testXml_header() {
    $this->assertEquals( '<?xml version="1.0" encoding="utf-8"?>' . "\n", DAV::xml_header(), 'DAV::xml_header() should return the correct value' );
  }


//  public function testXmlescape() {
//  }
//
//
//  public function testXmlunescape() {
//  }


} // End of DAVTest

// End of file