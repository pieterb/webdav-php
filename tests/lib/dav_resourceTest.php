<?php
/**
 * Contains tests for the DAV_Resource class
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
 * Contains tests for the DAV_Resource class
 * @package DAV
 * @subpackage tests
 * @todo er zitten veel matige tests in deze klasse. Even nakijken of ik mbv stubs toch iets meer kan testen
 */
class DAV_ResourceTest extends PHPUnit_Framework_TestCase {
  
  private $obj = null;
  
  
  public function setUp() {
    $this->obj = new DAV_Resource( '/collection/child' );
  }
  

  public function assertLock() {
    if ( !DAV::$LOCKPROVIDER ) return null;
    if ( ( $lock = DAV::$LOCKPROVIDER->getlock($this->path) ) &&
         !isset( DAV::$SUBMITTEDTOKENS[$lock->locktoken] ) )
      throw new DAV_Status(
        DAV::HTTP_LOCKED, array(
          DAV::COND_LOCK_TOKEN_SUBMITTED =>
            new DAV_Element_href( $lock->lockroot )
        )
      );
  }


  /**
   * @param string $path
   * @return mixed one of the following:
   * - DAV_Element_href of the lockroot of the missing token
   * - null if no lock was found.
   */
  public function assertMemberLocks() {
    if ( !DAV::$LOCKPROVIDER ) return;
    if ( ! $this instanceof DAV_Collection ) return;
    $locks = DAV::$LOCKPROVIDER->memberLocks( $this->path );
    $unsubmitted = array();
    foreach ($locks as $token => $lock)
      if ( !isset( DAV::$SUBMITTEDTOKENS[$token] ) )
        $unsubmitted[] =
          DAV::$REGISTRY->resource($lock->lockroot)->isVisible() ?
          $lock->lockroot : '/';
    if ( !empty( $unsubmitted ) )
      throw new DAV_Status(
        DAV::HTTP_LOCKED, array(
          DAV::COND_LOCK_TOKEN_SUBMITTED => new DAV_Element_href($unsubmitted)
        )
      );
  }


  public function testCollection() {
    $root = new DAV_Resource( '/' );
    $this->assertNull( $root->collection(), 'DAV_Resource::collection() should return null for the root resource' );
    $this->assertSame( $this->obj->collection()->path, '/collection', 'DAV_Resource::collection() should return the correct collection for the non-root resource' );
  }
  

  public function testConstructor() {
    $this->assertSame( '/collection/child', $this->obj->path, 'Constructor of DAV_Resource should set object property $path correctly' );
  }


  public function testIsVisible() {
    $this->assertTrue( $this->obj->isVisible(), 'Default implementation of DAV_Resource::isVisible() should always return true' );
  }


  public function testMethod_COPY() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $this->obj->method_COPY( '/destination/path' );
  }


  public function testMethod_COPY_external() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $this->obj->method_COPY_external( '/destination/path', true );
  }


  public function testMethod_GET() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $this->obj->method_GET();
  }


  public function testMethod_HEAD() {
    $this->assertSame( array(), $this->obj->method_HEAD(), 'Default implementation of DAV_Resource::method_HEAD() should return an empty array' );
  }


  public function testMethod_OPTIONS() {
    $param = array( 'header1' => 'value1', 'header2' => 'value2' );
    $this->assertSame( $param, $this->obj->method_OPTIONS( $param ), 'Default implementation of DAV_Resource::method_OPTIONS() should return all headers provided through the parameter' );
  }


  public function testMethod_POST() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $param = array();
    $this->obj->method_POST( $param );
  }


  public function testMethod_PROPPATCH() {
    // In the default implementation it is forbidden to set any property (but method_PROPPATCH is implemented!)
    $this->setExpectedException( 'DAV_Status', '', 403 );
    $this->obj->method_PROPPATCH( 'someprop', 'somevalue' );
  }


  public function testMethod_PUT() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $this->obj->method_PUT( STDIN );
  }

  
  public function testMethod_PUT_range() {
    // We expect an error instead of a DAV_Status because DAV_Status with a code >= 500 will trigger an error too
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning', '', 512 );
    $this->obj->method_PUT_range( STDIN, 1, 5, 10 );
  }


  public function testUser_prop_creationdate() {
    $this->assertNull( $this->obj->user_prop_creationdate(), 'The default implementation of DAV_Resource::user_prop_creationdate() should return null');
  }


  public function testUser_prop_displayname() {
    $this->assertNull( $this->obj->user_prop_displayname(), 'The default implementation of DAV_Resource::user_prop_displayname() should return null');
  }


  public function testUser_prop_executable() {
    $this->assertNull( $this->obj->user_prop_executable(), 'The default implementation of DAV_Resource::user_prop_executable() should return null');
  }


  public function testUser_prop_getcontentlanguage() {
    $this->assertNull( $this->obj->user_prop_getcontentlanguage(), 'The default implementation of DAV_Resource::user_prop_getcontentlanguage() should return null');
  }


  public function testUser_prop_getcontentlength() {
    $this->assertNull( $this->obj->user_prop_getcontentlength(), 'The default implementation of DAV_Resource::user_prop_getcontentlength() should return null');
  }


  public function testUser_prop_getcontenttype() {
    $this->assertNull( $this->obj->user_prop_getcontenttype(), 'The default implementation of DAV_Resource::user_prop_getcontenttype() should return null');
  }


  public function testUser_prop_getetag() {
    $this->assertNull( $this->obj->user_prop_getetag(), 'The default implementation of DAV_Resource::user_prop_getetag() should return null');
  }


  public function testUser_prop_getlastmodified() {
    $this->assertNull( $this->obj->user_prop_getlastmodified(), 'The default implementation of DAV_Resource::user_prop_getlastmodified() should return null');
  }


  public function testUser_prop_resourcetype() {
    $this->assertNull( $this->obj->user_prop_resourcetype(), 'The default implementation of DAV_Resource::user_prop_resourcetype() should return null');
  }


  public function testPropname() {
    $this->assertSame( array( 'DAV: supported-report-set' => true ), $this->obj->propname(), 'The default implementation of DAV_Resource::propname() should only return DAV: supported-report-set' );
  }


  public function testProperty_priv_read() {
    $this->assertNotContains( false, $this->obj->property_priv_read( $this->obj->propname() ), 'DAV_Resource::property_priv_read() should indicate that all properties are readable' );
  }


  public function testProperty_priv_write() {
    $this->assertNotContains( true, $this->obj->property_priv_write( $this->obj->propname() ), 'DAV_Resource::property_priv_write() should indicate that no properties are writable' );
  }


  public function testProp_creationdate() {
    $this->assertNull( $this->obj->prop_creationdate(), 'DAV_Resource::prop_creationdate() should return the correct value' );
  }


  public function testProp_displayname() {
    $this->assertNull( $this->obj->prop_creationdate(), 'DAV_Resource::prop_displayname() should return the correct value' );
  }


  public function testSet_displayname() {
    // In the default implementation it is forbidden to set any property
    $this->setExpectedException( 'DAV_Status', '', 403 );
    $this->obj->set_displayname( 'a new displayname' );
  }


  public function testProp_executable() {
    $this->assertNull( $this->obj->prop_executable(), 'DAV_Resource::prop_executable() should return the correct value' );
  }


  public function testSet_executable() {
    // In the default implementation it is forbidden to set any property
    $this->setExpectedException( 'DAV_Status', '', 403 );
    $this->obj->set_executable( true );
  }


  public function testProp_getcontentlanguage() {
    $this->assertNull( $this->obj->prop_getcontentlanguage(), 'DAV_Resource::prop_getcontentlanguage() should return the correct value' );
  }


  public function testSet_getcontentlanguageWrong() {
    // In the default implementation only accepts valid languages
    $this->setExpectedException( 'DAV_Status', '', 400 );
    $this->obj->set_getcontentlanguage( 'no real language' );
  }
  
  
  public function testSet_getcontentlanguageCorrect() {
    // In the default implementation it is forbidden to set any property
    $this->setExpectedException( 'DAV_Status', '', 403 );
    $this->obj->set_getcontentlanguage( 'nl' );
  }


  public function testProp_getcontentlength() {
    $this->assertNull( $this->obj->prop_getcontentlength(), 'DAV_Resource::prop_getcontentlength() should return the correct value' );
  }


  public function testProp_getcontenttype() {
    $this->assertNull( $this->obj->prop_getcontenttype(), 'DAV_Resource::prop_getcontenttype() should return the correct value' );
  }


  public function testSet_getcontenttype() {
    $this->assertTrue( false, 'Continue with implementing tests from here' );
    if ( !is_null( $value ) ) {
      // RFC2616 §2.2
      // token          = 1*<any CHAR except CTLs or separators>
      // separators     = "(" | ")" | "<" | ">" | "@"
      //                | "," | ";" | ":" | "\" | <">
      //                | "/" | "[" | "]" | "?" | "="
      //                | "{" | "}" | SP | HT

      // A token. Note that it's escaped for use between @@ delimiters.
      $token = "[^\\x00-\\x20\\x7f-\\xff\\(\\)<>\\@,;:\\\\\"/\\[\\]?={}]+";
      //                                   escaped^         ^unescaped
      $quoted = "\"(?:\"\"|[\\x20-\\x7e]|\\r\\n[\\t ]+)*\"";
      if ( !preg_match( "@^{$token}/{$token}\\s*(.*)\$@s", $value, $matches ) ||
           !preg_match( "@^(?:;\\s*{$token}=(?:{$quoted}|{$token})\\s*)*\$@s", $matches[1] ) )
        throw new DAV_Status(
          DAV::HTTP_BAD_REQUEST,
          "'$value' is not a valid media type."
        );
    }
    return $this->user_set_getcontenttype($value);
  }


  /**
   * @return void
   * @throws DAV_Status
   */
  protected function user_set_getcontenttype($value) {
    throw new DAV_Status( DAV::HTTP_FORBIDDEN );
  }


  /**
   * @return string XML
   */
  final public function prop_getetag() {
    if (is_null($tmp = $this->user_prop_getetag())) return null;
    return DAV::xmlescape( $tmp );
  }


  /**
   * @return string XML
   */
  final public function prop_getlastmodified() {
    if (is_null($tmp = $this->user_prop_getlastmodified())) return null;
    return DAV::xmlescape( DAV::httpDate( $tmp ) );
  }


  /**
   * @return string XML
   */
  final public function prop_ishidden() {
    if (is_null($tmp = $this->user_prop_ishidden())) return null;
    return $tmp ? 'true' : 'false';
  }


  /**
   * @param string $value null, 'true' or 'false'
   * @return void
   * @throws DAV_Status
   */
  final public function set_ishidden($value) {
    if (!is_null($value)) $value = ($value === 'true');
    return $this->user_set_ishidden($value);
  }


  /*
   * @return void
   * @throws DAV_Status
   */
  //protected function user_set_ishidden($value) {
  //  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
  //}


  /*
   * @return string XML
   */
  //final public function prop_lastaccessed() {
  //  if (is_null($tmp = $this->user_prop_lastaccessed())) return null;
  //  return gmdate( 'Y-m-d\\TH:i:s\\Z', $tmp );
  //  //return DAV::xmlescape( DAV::httpDate($tmp) );
  //}


  /**
   * @return string XML
   */
  final public function prop_lockdiscovery() {
    if ( ! DAV::$LOCKPROVIDER ) return null;
    $retval = ( $lock = DAV::$LOCKPROVIDER->getlock($this->path) ) ?
      $lock->toXML() : '';
    return $retval;
  }


  /**
   * @return string XML
   */
  final public function prop_resourcetype() {
    $retval = $this->user_prop_resourcetype();
    if (!is_null($retval)) return $retval;
    if ($this instanceof DAV_Collection)
      $retval .= DAV_Collection::RESOURCETYPE;
    if ($this instanceof DAVACL_Principal)
      $retval .= DAVACL_Principal::RESOURCETYPE;
    return $retval;
  }


  /**
   * @return string XML
   */
  final public function prop_supported_report_set() {
    $retval = ($this instanceof DAVACL_Principal_Collection) ?
      DAV::$REPORTS :
      array(DAV::REPORT_EXPAND_PROPERTY);
    return '<D:supported-report><D:' .
      implode("/></D:supported-report>\n<D:supported-report><D:", $retval) .
      '/></D:supported-report>';
  }


  /**
   * @return string XML
   */
  final public function prop_supportedlock() {
    if ( ! DAV::$LOCKPROVIDER ) return null;
    return <<<EOS
<D:lockentry>
  <D:lockscope><D:exclusive/></D:lockscope>
  <D:locktype><D:write/></D:locktype>
</D:lockentry>
EOS;
  }


  /**
   * @param string $propname the name of the property to be returned,
   *        eg. "mynamespace: myprop"
   * @return string XML or NULL if the property is not defined.
   */
  public function prop($propname) {
    if ($method = @DAV::$WEBDAV_PROPERTIES[$propname])
      return call_user_func(array($this, "prop_$method"));
    return $this->user_prop($propname);
  }


  /**
   * Stores properties set earlier by set().
   * @return void
   * @throws DAV_Status in particular 507 (Insufficient Storage)
   */
  public function storeProperties() {
    throw new DAV_Status( DAV::HTTP_FORBIDDEN );
  }

} // class DAV_ResourceTest

// End of file