<?php
/**
 * Contains tests for the DAVACL_Resource class
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
 * Contains tests for the DAVACL_Resource class
 * @package DAV
 * @subpackage tests
 */
class DAVACL_ResourceTest extends PHPUnit_Framework_TestCase {

  /**
   * @var  DAVACL_ResourceTest  The unit under test
   */
  private $obj = null;
  
  
  public function setUp() {
    $this->obj = new DAVACL_Test_Principal( '/collection/child' );
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Principal' );
  }


  public function testAssert() {
    // TODO
  }

  public function testCurrent_user_principals() {
    $expected = array(
      'DAV: all' => 'DAV: all',
      '/path/to/current/user' => '/path/to/current/user',
      '/path/to/group' => '/path/to/group',
      'DAV: authenticated' => 'DAV: authenticated'
    );
    $this->assertSame( $expected, $this->obj->current_user_principals(), 'DAVACL_Resource::current_user_principals() should return the complete list of principals the current user maps to' );
  }


  public function testEffective_acl() {
    // TODO
  }


  public function testMethod_HEAD() {
    $objAllow = $this->getMock( 'DAVACL_Test_Principal', array( 'assert' ), array( '/collection/child' ) );
    $objAllow->expects( $this->once() )
             ->method( 'assert')
             ->with( $this->equalTo( DAVACL::PRIV_READ ) )
             ->will( $this->returnValue( true ) );

    $this->assertSame( array(), $objAllow->method_HEAD(), 'DAVACL_Resource::method_HEAD() should return headers when assert() returns true' );

    $objDeny = $this->getMock( 'DAVACL_Test_Principal', array( 'assert' ), array( '/collection/child' ) );
    $objDeny->expects( $this->once() )
            ->method( 'assert')
            ->with( $this->equalTo( DAVACL::PRIV_READ ) )
            ->will( $this->returnValue( false ) );

    $this->assertSame( array(), $objDeny->method_HEAD(), 'DAVACL_Resource::method_HEAD() should return headers when assert() returns true' );
  }


  public function testProp_acl() {
    $acl = array( new DAVACL_Element_ace( '/path/to/user', false, array( DAVACL::PRIV_ALL ), false ) );
    $acl[] = new DAVACL_Element_ace( '/path/to/other/user', false, array( DAVACL::PRIV_ALL ), false );
    $acl[] = new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, array( DAVACL::PRIV_ALL ), false );
    $resource = $this->getMock( 'DAVACL_Test_Resource', array( 'user_prop_acl' ), array( $_SERVER['REQUEST_URI'] ) );
    $resource->expects( $this->any() )
             ->method( 'user_prop_acl' )
             ->will( $this->returnValue( $acl ) );
    
    $expected = <<<EOS
<D:ace>
<D:principal><D:href>/path/to/user</D:href></D:principal>
<D:grant><D:all/></D:grant>
</D:ace>
<D:ace>
<D:principal><D:href>/path/to/other/user</D:href></D:principal>
<D:grant><D:all/></D:grant>
</D:ace>
<D:ace>
<D:principal><D:all/></D:principal>
<D:grant><D:all/></D:grant>
</D:ace>
EOS
    ;
    $this->assertSame( $expected, $resource->prop_acl(), 'DAVACL_Resource::prop_acl() should return ACL in XML format' );
  }
  
  
  public function testProp_acl_restrictions() {
    // TODO
//    $this->assertSame( 'a', $this->obj->prop_acl_restrictions(), 'DAVACL_Resource::prop_acl_restrictions() should return the correct value' );
  }


  public function testProp_alternate_URI_set() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_alternate_URI_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_alternate_URI_set' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_alternate_URI_set();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_current_user_principal() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_current_user_principal' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_current_user_principal' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_current_user_principal();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_current_user_privilege_set() {
    // TODO
  }


  public function testProp_group() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_group' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_group' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_group();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_group_member_set() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_group_member_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_group_member_set' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_group_member_set();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_group_membership() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_group_membership' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_group_membership' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_group_membership();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_inherited_acl_set() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_inherited_acl_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_inherited_acl_set' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_inherited_acl_set();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_owner() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_owner' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_owner' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_owner();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_principal_URL() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_principal_URL' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_principal_URL' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_principal_URL();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }


  public function testProp_principal_collection_set() {
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_principal_collection_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_principal_collection_set' )
        ->will( $this->returnValue( '/some/other/path' ) );
    $returned = $obj->prop_principal_collection_set();

    $this->assertInstanceof( 'DAV_Element_href', $returned );
    $this->assertSame( array( '/some/other/path' ), $returned->URIs );
  }
  
  
  public function testProp_supported_privilege_set() {
    $return = array(
        new DAVACL_Element_supported_privilege( DAVACL::PRIV_READ, true, 'You can read the resource and the ACL' ),
        new DAVACL_Element_supported_privilege( DAVACL::PRIV_WRITE, false, 'You can write to the resource' )
    );
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_prop_supported_privilege_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_prop_supported_privilege_set' )
        ->will( $this->returnValue( $return ) );
    $returned = $obj->prop_supported_privilege_set();
    $expected = <<<EOS
<D:supported-privilege>
<D:privilege><D:read/></D:privilege>
<D:abstract/>
<D:description>You can read the resource and the ACL</D:description>
</D:supported-privilege>
<D:supported-privilege>
<D:privilege><D:write/></D:privilege>
<D:description>You can write to the resource</D:description>
</D:supported-privilege>
EOS
    ;
    
    $this->assertSame( $expected, $returned, 'DAVACL_Resource::prop_supported_privilege_set should return the right XML description' );
  }
  
  
  public function testProperty_priv_read() {
    $properties = array(
        DAV::PROP_DISPLAYNAME,
        DAV::PROP_GETCONTENTLENGTH
    );
    $expected = array(
        DAV::PROP_DISPLAYNAME => true,
        DAV::PROP_GETCONTENTLENGTH => true
    );
    $this->assertSame( $expected, $this->obj->property_priv_read( $properties ), 'DAVACL_Resource::property_priv_read() should return the right properties as readable' );
  }
  
  
  public function testProperty_priv_write() {
    $properties = array(
        DAV::PROP_DISPLAYNAME,
        DAV::PROP_GETCONTENTLENGTH
    );
    $expected = array(
        DAV::PROP_DISPLAYNAME => true,
        DAV::PROP_GETCONTENTLENGTH => true
    );
    $this->assertSame( $expected, $this->obj->property_priv_write( $properties ), 'DAVACL_Resource::property_priv_write() should return the right properties as writable' );
  }
  
  
  public function testPropname() {
    $expected = array(
        'DAV: lockdiscovery' => true,
        'DAV: resourcetype' => true,
        'DAV: supportedlock' => true,
        'DAV: supported-report-set' => true,
        'DAV: owner' => false,
        'DAV: group' => false,
        'DAV: supported-privilege-set' => false,
        'DAV: current-user-privilege-set' => false,
        'DAV: acl' => false,
        'DAV: acl-restrictions' => false,
        'DAV: inherited-acl-set' => false,
        'DAV: principal-collection-set' => false,
        'DAV: current-user-principal' => false,
        'DAV: alternate-URI-set' => false,
        'DAV: principal-URL' => false,
        'DAV: group-member-set' => false,
        'DAV: group-membership' => false
    );
    $this->assertSame( $expected, $this->obj->propname(), 'DAVACL_Resource::propname() should return all properties correctly' );
  }
  
  
  public function testSet_acl() {
    $acl = array(
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, DAVACL::PRIV_WRITE, false ),
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_AUTHENTICATED, true, DAVACL::PRIV_READ, true )
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl', 'user_set_acl' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_set_acl' )
        ->with( $this->equalTo( $acl ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' );
    
    $obj->set_acl( $acl );
  }
  
  
  public function testSet_group() {
    // If we have a correct request, then user_set_group should be called with the group path
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_set_group' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_set_group' )
        ->with( $this->equalTo( '/some/path/to/somewhere' ) );
    $obj->set_group( '<D:href>http://example.org/some/path/to/somewhere</D:href>' );
    
    // And if we sent to much groups, it is a wrong request
    $this->setExpectedException( 'DAV_Status', 400 );
    $this->obj->set_group( '<D:href>http://example.org/some/path/to/somewhere</D:href><D:href>http://example.org/some/path/to/somewhere/else</D:href>' );
  }
  
  
  public function testSet_group_member_set() {
    // If we have a correct request, then user_set_group should be called with the group path
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_set_group_member_set' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_set_group_member_set' )
        ->with( $this->equalTo( array(
            '/some/path/to/somewhere',
            '/some/path/to/somewhere/else'
        ) ) );
    $obj->set_group_member_set( '<D:href>http://example.org/some/path/to/somewhere</D:href><D:href>http://example.org/some/path/to/somewhere/else</D:href>' );
  }
  
  
  public function testSet_owner() {
    // If we have a correct request, then user_set_group should be called with the group path
    $obj = $this->getMock( 'DAVACL_Test_Principal', array('user_set_owner' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->once() )
        ->method( 'user_set_owner' )
        ->with( $this->equalTo( '/some/path/to/somewhere' ) );
    $obj->set_owner( '<D:href>http://example.org/some/path/to/somewhere</D:href>' );
    
    // And if we sent to much groups, it is a wrong request
    $this->setExpectedException( 'DAV_Status', 400 );
    $this->obj->set_owner( '<D:href>http://example.org/some/path/to/somewhere</D:href><D:href>http://example.org/some/path/to/somewhere/else</D:href>' );
  }
  
  
  public function testUser_prop_current_user_privilege_set() {
    $this->assertSame( '', $this->obj->user_prop_current_user_privilege_set(), 'DAVAC_Resource::user_prop_current_user_privilege_set() should return the correct value' );
  }
  
  
  public function testUser_set_acl() {
    $acl = array(
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, DAVACL::PRIV_WRITE, false ),
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_AUTHENTICATED, true, DAVACL::PRIV_READ, true )
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl' ), array( $_SERVER['REQUEST_URI']  ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' );
    
    $this->setExpectedException( 'DAV_Status', 403 );
    $obj->set_acl( $acl );
  }

} // class DAVACL_ResourceTest

// End of file
