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
 */
class DAV_ResourceTest extends PHPUnit_Framework_TestCase {
  
  private $obj = null;
  
  
  public function setUp() {
    $this->obj = new DAV_Resource( '/collection/child' );
    DAV::$REGISTRY->setResourceClass( 'DAV_Resource' );
  }
  

  public function testAssertLock() {
    $lock = new DAV_Element_activelock( array (
        'lockroot' => '/collection',
        'locktoken' => 'thelocktoken',
        'owner' => '/path/to/user',
        'timeout' => time() + 3600
    ) );
    
    $lockProviderStub = $this->getMock( 'DAV_Lock_Provider' );
    $lockProviderStub->expects( $this->atLeastOnce() )
                     ->method( 'getlock' )
                     ->with( $this->equalTo( '/collection/child' ) )
                     ->will( $this->returnValue( $lock ) );
    DAV::$LOCKPROVIDER = $lockProviderStub;
    
    DAV::$SUBMITTEDTOKENS[ 'thelocktoken' ] = 'thelocktoken';
    $this->obj->assertLock(); // No need to assert anything; if the resource is locked (which it should not be), an exception will be thrown
    
    unset( DAV::$SUBMITTEDTOKENS[ 'thelocktoken' ] );
    $this->setExpectedException( 'DAV_Status', '', 423 );
    $this->obj->assertLock();
  }


  public function testAssertMemberLocks() {
    $lock = new DAV_Element_activelock( array (
        'lockroot' => '/collection/child/subresource',
        'locktoken' => 'thelocktoken',
        'owner' => '/path/to/user',
        'timeout' => time() + 3600
    ) );
    
    $lockProviderStub = $this->getMock( 'DAV_Lock_Provider' );
    $lockProviderStub->expects( $this->atLeastOnce() )
                     ->method( 'memberLocks' )
                     ->with( $this->equalTo( '/collection/child' ) )
                     ->will( $this->returnValue( array( 'thelocktoken' => $lock ) ) );
    DAV::$LOCKPROVIDER = $lockProviderStub;
    
    DAV::$SUBMITTEDTOKENS[ 'thelocktoken' ] = 'thelocktoken';
    $obj = new DAV_Resource_DAV_Collection_TestImplementation( '/collection/child' );
    $obj->assertMemberLocks(); // No need to assert anything; if the resource is locked (which it should not be), an exception will be thrown
    
    unset( DAV::$SUBMITTEDTOKENS[ 'thelocktoken' ] );
    $this->setExpectedException( 'DAV_Status', '', 423 );
    $obj->assertMemberLocks();
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
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Lock_Provider' );
    $expectedBasic = array(
        'DAV: supported-report-set' => true,
        'DAV: lockdiscovery' => true,
        'DAV: supportedlock' => true
    );
    ksort( $expectedBasic );
    $returnedBasic = $this->obj->propname();
    ksort( $returnedBasic );
    $this->assertSame( $expectedBasic, $returnedBasic, 'The default implementation of DAV_Resource::propname() should only return DAV: supported-report-set' );
    
    // Mock it, so we can test some more
    $userProps = array(
        'NS prop1' => true,
        'NS prop2' => false,
        'NS prop3' => true
    );
    $stub = $this->getMock( 'DAV_Resource', array( 'user_propname', 'user_prop_displayname' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_propname' )
         ->will( $this->returnValue( $userProps ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_displayname' )
         ->will( $this->returnValue( 'Some displayname' ) );
    
    // And check whether we get back what we expect
    $expected = array(
        'NS prop1' => true,
        'NS prop2' => false,
        'NS prop3' => true,
        'DAV: displayname' => true,
        'DAV: supported-report-set' => true,
        'DAV: lockdiscovery' => true,
        'DAV: supportedlock' => true
    );
    ksort( $expected );
    $returned = $stub->propname();
    ksort( $returned );
    $this->assertSame( $expected, $returned, 'DAV_Resource::propname should return the correct values' );
  }


  public function testProperty_priv_read() {
    $this->assertNotContains( false, $this->obj->property_priv_read( $this->obj->propname() ), 'DAV_Resource::property_priv_read() should indicate that all properties are readable' );
  }


  public function testProperty_priv_write() {
    $this->assertNotContains( true, $this->obj->property_priv_write( $this->obj->propname() ), 'DAV_Resource::property_priv_write() should indicate that no properties are writable' );
  }


  public function testProp_creationdate() {
    $this->assertNull( $this->obj->prop_creationdate(), 'DAV_Resource::prop_creationdate() should return the correct value' );

    $time = gmmktime( 4, 5, 6, 2, 1, 2013 );
    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_creationdate' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_creationdate' )
         ->will( $this->returnValue( $time ) );
    $this->assertSame( '2013-02-01T04:05:06Z', $stub->prop_creationdate(), 'DAV_Resource::prop_creationdate() should convert the timestamp to a correct string' );
  }


  public function testProp_displayname() {
    $this->assertNull( $this->obj->prop_displayname(), 'DAV_Resource::prop_displayname() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_displayname' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_displayname' )
         ->will( $this->returnValue( 'Some & displayname' ) );
    $this->assertSame( 'Some &amp; displayname', $stub->prop_displayname(), 'DAV_Resource::prop_displayname() should XML escape the displayname' );
  }


  public function testSet_displayname() {
    $stub = $this->getMock( 'DAV_Resource', array( 'user_set_displayname' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_set_displayname' )
         ->with( $this->equalTo( 'some & value' ) );
    $stub->set_displayname( 'some &amp; value' );
    
    // XML is not allowed in the displayname (so you should have escaped < )
    $this->setExpectedException( 'DAV_Status', '', 400 );
    $this->obj->set_displayname( 'no <xml> allowed' );
  }


  public function testProp_executable() {
    $this->assertNull( $this->obj->prop_executable(), 'DAV_Resource::prop_executable() should return the correct value' );
    
    $stubTrue = $this->getMock( 'DAV_Resource', array( 'user_prop_executable' ), array( '/path' ) );
    $stubTrue->expects( $this->once() )
             ->method( 'user_prop_executable' )
             ->will( $this->returnValue( true ) );
    $stubFalse = $this->getMock( 'DAV_Resource', array( 'user_prop_executable' ), array( '/path' ) );
    $stubFalse->expects( $this->once() )
              ->method( 'user_prop_executable' )
              ->will( $this->returnValue( false ) );
    
    $this->assertSame( 'T', $stubTrue->prop_executable(), 'DAV_Resource::prop_executable() should convert true to T' );
    $this->assertSame( 'F', $stubFalse->prop_executable(), 'DAV_Resource::prop_executable() should convert false to F' );
  }


  public function testSet_executable() {
    $stubTrue = $this->getMock( 'DAV_Resource', array( 'user_set_executable' ), array( '/path' ) );
    $stubTrue->expects( $this->once() )
             ->method( 'user_set_executable' )
             ->with( $this->equalTo( true ) );
    $stubFalse = $this->getMock( 'DAV_Resource', array( 'user_set_executable' ), array( '/path' ) );
    $stubFalse->expects( $this->once() )
              ->method( 'user_set_executable' )
              ->with( $this->equalTo( false ) );

    $stubTrue->set_executable( 'T' );
    $stubFalse->set_executable( 'F' );
  }


  public function testProp_getcontentlanguage() {
    $this->assertNull( $this->obj->prop_getcontentlanguage(), 'DAV_Resource::prop_getcontentlanguage() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_getcontentlanguage' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_getcontentlanguage' )
         ->will( $this->returnValue( 'nl<xml>' ) );
    $this->assertSame( 'nl&lt;xml&gt;', $stub->prop_getcontentlanguage(), 'DAV_Resource::prop_getcontentlanguage() should XML escape the language' );
    
  }


  public function testSet_getcontentlanguage() {
    $stub = $this->getMock( 'DAV_Resource', array( 'user_set_getcontentlanguage' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_set_getcontentlanguage' )
         ->with( $this->equalTo( 'nl, en, es' ) );
    $stub->set_getcontentlanguage( 'nl, en, es' );
    
    // In the default implementation only accepts valid languages
    $this->setExpectedException( 'DAV_Status', '', 400 );
    $this->obj->set_getcontentlanguage( 'no real language' );
  }


  public function testProp_getcontentlength() {
    $this->assertNull( $this->obj->prop_getcontentlength(), 'DAV_Resource::prop_getcontentlength() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_getcontentlength' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_getcontentlength' )
         ->will( $this->returnValue( '500<xml>' ) );
    $this->assertSame( '500&lt;xml&gt;', $stub->prop_getcontentlength(), 'DAV_Resource::prop_getcontentlength() should XML escape the language' );
  }


  public function testProp_getcontenttype() {
    $this->assertNull( $this->obj->prop_getcontenttype(), 'DAV_Resource::prop_getcontenttype() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_getcontenttype' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_getcontenttype' )
         ->will( $this->returnValue( 'text/<xml>' ) );
    $this->assertSame( 'text/&lt;xml&gt;', $stub->prop_getcontenttype(), 'DAV_Resource::prop_getcontenttype() should XML escape the language' );
  }


  public function testSet_getcontenttype() {
    $stub = $this->getMock( 'DAV_Resource', array( 'user_set_getcontenttype' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_set_getcontenttype' )
         ->with( $this->equalTo( 'text/plain' ) );
    $stub->set_getcontenttype( 'text/plain' );
    
    // In the default implementation it is forbidden to set an invalid content type
    $this->setExpectedException( 'DAV_Status', '', 400 );
    $this->obj->set_getcontenttype( 'this is a text document' );
  }


  public function testProp_getetag() {
    $this->assertNull( $this->obj->prop_getetag(), 'DAV_Resource::prop_getetag() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_getetag' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_getetag' )
         ->will( $this->returnValue( 'ETag with <xml>' ) );
    $this->assertSame( 'ETag with &lt;xml&gt;', $stub->prop_getetag(), 'DAV_Resource::prop_getetag() should XML escape the language' );
  }


  public function testProp_getlastmodified() {
    $this->assertNull( $this->obj->prop_getlastmodified(), 'DAV_Resource::prop_getlastmodified() should return the correct value' );

    $time = gmmktime( 4, 5, 6, 2, 1, 2013 );
    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_getlastmodified' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_getlastmodified' )
         ->will( $this->returnValue( $time ) );
    $this->assertSame( 'Fri, 01 Feb 2013 04:05:06 GMT', $stub->prop_getlastmodified(), 'DAV_Resource::prop_getlastmodified() should return a correctly formated date' );
  }


  public function testProp_lockdiscovery() {
    $lock = new DAV_Element_activelock( array (
        'lockroot' => '/collection',
        'locktoken' => 'thelocktoken',
        'owner' => '/path/to/user',
        'timeout' => time() + 3600
    ) );
    
    $lockProviderStub = $this->getMock( 'DAV_Lock_Provider' );
    $lockProviderStub->expects( $this->atLeastOnce() )
                     ->method( 'getlock' )
                     ->with( $this->equalTo( '/collection/child' ) )
                     ->will( $this->returnValue( $lock ) );
    DAV::$LOCKPROVIDER = $lockProviderStub;
    
    $this->assertsame( $lock->toXML(), $this->obj->prop_lockdiscovery(), 'DAV_Resource::prop_lockdiscovery() should return the correct value' );
  }


  public function testProp_resourcetype() {
    $this->assertNull( $this->obj->prop_resourcetype(), 'DAV_Resource::prop_resourcetype() should return the correct value' );

    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop_resourcetype' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop_resourcetype' )
         ->will( $this->returnValue( '<D:unexisting_type/>' ) );
    $this->assertSame( '<D:unexisting_type/>', $stub->prop_resourcetype(), 'DAV_Resource::prop_resourcetype() should return the value returned by DAV_Resource::user_prop_resourcetype()' );
    
    $collection = new DAV_Resource_DAV_Collection_TestImplementation( '/path' );
    $principal = new DAV_Resource_DAVACL_Principal_TestImplementation( '/path' );
    $this->assertSame( DAV_Collection::RESOURCETYPE, $collection->prop_resourcetype(), 'DAV_Resource::prop_resourcetype should indicate being a collection if DAV_Collection is implemented' );
    $this->assertSame( DAVACL_Principal::RESOURCETYPE, $principal->prop_resourcetype(), 'DAV_Resource::prop_resourcetype should indicate being a collection if DAVACL_Principal is implemented' );
  }


  public function testProp_supported_report_set() {
    $this->assertSame( '<D:supported-report><D:expand-property/></D:supported-report>', $this->obj->prop_supported_report_set(), 'DAV_Resource::prop_supported_report_set() should return the correct value' );
    
    $principalCollection = new DAV_Resource_DAVACL_Principal_Collection_TestImplementation( '/path' );
    $this->assertSame( "<D:supported-report><D:expand_property/></D:supported-report>\n<D:supported-report><D:acl_principal_prop_set/></D:supported-report>\n<D:supported-report><D:principal_match/></D:supported-report>\n<D:supported-report><D:principal_property_search/></D:supported-report>\n<D:supported-report><D:principal_search_property_set/></D:supported-report>", $principalCollection->prop_supported_report_set(), 'DAV_Resource::prop_supported_report_set() should return the correct value when the resource is a principal collection' );
  }


  public function testProp_supportedlock() {
    DAV::$LOCKPROVIDER = null;
    $this->assertNull( $this->obj->prop_supportedlock(), 'DAV_Resource::prop_supportedlock() should return the correct value' );
    
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Lock_Provider' );
    $this->assertSame( "<D:lockentry>\n  <D:lockscope><D:exclusive/></D:lockscope>\n  <D:locktype><D:write/></D:locktype>\n</D:lockentry>", $this->obj->prop_supportedlock(), 'DAV_Resource::prop_supportedlock() should return the correct value if DAV::$LOCKPROVIDER is set' );
  }


  public function testProp() {
    $stub = $this->getMock( 'DAV_Resource', array( 'user_prop', 'prop_displayname' ), array( '/path' ) );
    $stub->expects( $this->once() )
         ->method( 'user_prop' )
         ->with( $this->equalTo( 'NS: testproperty' ) )
         ->will( $this->returnValue( 'Some &amp; value' ) );
    
    $this->assertSame( 'Some &amp; value', $stub->prop( 'NS: testproperty' ), 'DAV_Resource::prop() should return an unchanged version of what DAV_Resource::user_prop() returns' );
  }


  public function testStoreProperties() {
    // In the default implementation it is forbidden to set any property
    $this->setExpectedException( 'DAV_Status', '', 403 );
    $this->obj->storeProperties();
  }

} // class DAV_ResourceTest


/**
 * Nothing is implemented as this class is only needed to test DAV_Resource::prop_resourcetype()
 */
class DAV_Resource_DAV_Collection_TestImplementation extends DAV_Resource implements DAV_Collection {
  
  public function create_member($name) {}

  public function current() {}

  public function key() {}

  public function method_DELETE($name) {}

  public function method_MKCOL($name) {}

  public function method_MOVE($member, $destination) {}

  public function next() {}

  public function rewind() {}

  public function valid() {}
  
} // class DAV_Resource_DAV_Collection_TestImplementation


/**
 * Nothing is implemented as this class is only needed to test DAV_Resource::prop_resourcetype()
 */
class DAV_Resource_DAVACL_Principal_TestImplementation extends DAV_Resource implements DAVACL_Principal {
  
  public function user_prop_alternate_uri_set() {}

  public function user_prop_group_member_set() {}

  public function user_prop_group_membership() {}

  public function user_prop_principal_url() {}

  public function user_set_group_member_set($set) {}
  
} // class DAV_Resource_DAV_Collection_TestImplementation


/**
 * Nothing is implemented as this class is only needed to test DAV_Resource::prop_resourcetype()
 */
class DAV_Resource_DAVACL_Principal_Collection_TestImplementation extends DAV_Resource implements DAVACL_Principal_Collection {

  public function report_principal_match($input) {}

  public function report_principal_property_search($input) {}

  public function report_principal_search_property_set() {}

} // class DAV_Resource_DAVACL_Principal_Collection_TestImplementation

// End of file