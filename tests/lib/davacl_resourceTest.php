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

} // class DAVACL_ResourceTest

// End of file
