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
  
  
  /**
   * Prepares a mocked DAVACL_Resource object which is needed by multiple tests (but not all)
   * 
   * @return  DAVACL_Resource  The mocked object
   */
  private function prepareObjWithAcl() {
    $_SERVER['REQUEST_URI'] = '/path/to/principal';
    $allAce = new DAVACL_Element_supported_privilege( DAVACL::PRIV_ALL, false, '' );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_BIND, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_READ, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_READ_ACL, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_READ_CURRENT_USER_PRIVILEGE_SET, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_UNBIND, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_UNLOCK, false, '' ) );
    $allAce->add_supported_privilege( new DAVACL_Element_supported_privilege( DAVACL::PRIV_WRITE_CONTENT, false, '' ) );
    $supportedPrivs = array( $allAce );
    DAV::$ACLPROVIDER = new DAVACL_Test_ACL_Provider();
    DAV::$ACLPROVIDER->setSupportedPrivilegeSet( $supportedPrivs );
    $acl = array(
        new DAVACL_Element_ace( '/path/to/principal', true, array( DAVACL::PRIV_BIND ), false), // Not effective
        new DAVACL_Element_ace( '/path/to/other/principal', false, array( DAVACL::PRIV_READ ), false), // Not effective
        new DAVACL_Element_ace( '/path/to/other/principal', true, array( DAVACL::PRIV_READ_ACL ), false), // Effective
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, array( DAVACL::PRIV_READ_CURRENT_USER_PRIVILEGE_SET ), true), // Effective
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_AUTHENTICATED, false, array( DAVACL::PRIV_UNBIND ), false), // Effective
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_UNAUTHENTICATED, false, array( DAVACL::PRIV_UNLOCK ), false), // Not effective
        new DAVACL_Element_ace( DAVACL::PRINCIPAL_SELF, false, array( DAVACL::PRIV_WRITE_CONTENT ), false) // Effective
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl', 'user_prop_current_user_principal', 'user_prop_supported_privilege_set' ), array( $_SERVER['REQUEST_URI'] ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' )
        ->will( $this->returnValue( $acl ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_current_user_principal' )
        ->will( $this->returnValue( '/path/to/principal' ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_supported_privilege_set' )
        ->will( $this->returnValue( $supportedPrivs ) );
    
    return $obj;
  }


  public function testAssert() {
    $obj = $this->prepareObjWithAcl();
    $obj->clearAssertCache();
    $this->assertTrue( $obj->assert( DAVACL::PRIV_READ_ACL )     , 'DAVACL_Resource::assert() should assert that the user has PRIV_READ_ACL privileges' );
    $obj->clearAssertCache();
    $this->assertTrue( $obj->assert( DAVACL::PRIV_UNBIND )       , 'DAVACL_Resource::assert() should assert that the user has PRIV_UNBIND privileges' );
    $obj->clearAssertCache();
    $this->assertTrue( $obj->assert( DAVACL::PRIV_WRITE_CONTENT ), 'DAVACL_Resource::assert() should assert that the user has PRIV_WRITE_CONTENT privileges' );

    $obj->clearAssertCache();
    // DAVACL_Resource::assert() should not assert aggregate privileges
    $this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );
    $obj->assert( DAVACL::PRIV_ALL );
  }
  
  
  public function testAssertForReadCurrentUserPrivilegeSet() {
    $obj = $this->prepareObjWithAcl();
    $obj->clearAssertCache();
    try{
      $obj->assert( DAVACL::PRIV_READ_CURRENT_USER_PRIVILEGE_SET );
      $this->assertFalse( true, 'DAVACL_Resource::assert() should throw an exception when asserting PRIV_READ_CURRENT_USER_PRIVILEGE_SET' );
    }catch ( DAV_Status $exception ) {
      $this->assertSame( 403, $exception->getCode(), 'DAVACL_Resource::assert() should throw a DAV_Status exception with code 403 when asserting PRIV_READ_CURRENT_USER_PRIVILEGE_SET' );
      $this->assertSame( array( 'need-privileges' => '<D:read-current-user-privilege-set/>' ), $exception->conditions, 'DAVACL_Resource::assert() should throw a DAV_Status exception with the correct condition when asserting PRIV_READ_CURRENT_USER_PRIVILEGE_SET' );
    }
  }
    
  
  public function testAssertForBind() {
    $obj = $this->prepareObjWithAcl();
    $obj->clearAssertCache();
    try{
      $obj->assert( DAVACL::PRIV_BIND );
      $this->assertFalse( true, 'DAVACL_Resource::assert() should throw an exception when asserting PRIV_BIND' );
    }catch ( DAV_Status $exception ) {
      $this->assertSame( 403, $exception->getCode(), 'DAVACL_Resource::assert() should throw a DAV_Status exception with code 403 when asserting PRIV_BIND' );
      $this->assertSame( array( 'need-privileges' => '<D:bind/>' ), $exception->conditions, 'DAVACL_Resource::assert() should throw a DAV_Status exception with the correct condition when asserting PRIV_BIND' );
    }
  }
  
  
  public function testAssertForRead() {
    $obj = $this->prepareObjWithAcl();
    $obj->clearAssertCache();
    try{
      $obj->assert( DAVACL::PRIV_READ );
      $this->assertFalse( true, 'DAVACL_Resource::assert() should throw an exception when asserting PRIV_READ' );
    }catch ( DAV_Status $exception ) {
      $this->assertSame( 403, $exception->getCode(), 'DAVACL_Resource::assert() should throw a DAV_Status exception with code 403 when asserting PRIV_READ' );
      $this->assertSame( array( 'need-privileges' => '<D:read/>' ), $exception->conditions, 'DAVACL_Resource::assert() should throw a DAV_Status exception with the correct condition when asserting PRIV_READ' );
    }
  }
  
  
  public function testAssertForUnlock() {
    $obj = $this->prepareObjWithAcl();
    $obj->clearAssertCache();
    try{
      $obj->assert( DAVACL::PRIV_UNLOCK );
      $this->assertFalse( true, 'DAVACL_Resource::assert() should throw an exception when asserting PRIV_UNLOCK' );
    }catch ( DAV_Status $exception ) {
      $this->assertSame( 403, $exception->getCode(), 'DAVACL_Resource::assert() should throw a DAV_Status exception with code 403 when asserting PRIV_UNLOCK' );
      $this->assertSame( array( 'need-privileges' => '<D:unlock/>' ), $exception->conditions, 'DAVACL_Resource::assert() should throw a DAV_Status exception with the correct condition when asserting PRIV_UNLOCK' );
    }
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
    $obj = $this->prepareObjWithAcl();
    
    $expected = array(
        array( false, array( 'DAV: read-acl' ) ),
        array( true , array( 'DAV: read-current-user-privilege-set' ) ),
        array( false, array( 'DAV: unbind' ) ),
        array( false, array( 'DAV: write-content' ) )
    );
    
    $obj->clearEaclCache();
    $this->assertSame( $expected, $obj->effective_acl(), 'DAVACL_Resource::effective_acl() should return the right privileges' );
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
<D:grant><D:privilege><D:all/></D:privilege></D:grant>
</D:ace>
<D:ace>
<D:principal><D:href>/path/to/other/user</D:href></D:principal>
<D:grant><D:privilege><D:all/></D:privilege></D:grant>
</D:ace>
<D:ace>
<D:principal><D:all/></D:principal>
<D:grant><D:privilege><D:all/></D:privilege></D:grant>
</D:ace>
EOS
    ;
    $this->assertSame( $expected, $resource->prop_acl(), 'DAVACL_Resource::prop_acl() should return ACL in XML format' );
  }
  
  
  public function testProp_acl_restrictions() {
    $restrictions = array(
        array( // An array of principals and properties pointing to principals (i.e. 'owner' and the fictional 'property-pointing-to-principal') combined
            '/path/to/principal1',
            '/path/to/principal2',
            DAVACL::PRINCIPAL_UNAUTHENTICATED,
            DAV::PROP_OWNER,
            'http://test/ property-pointing-to-principal'
        ), // And then some more restrictions as defined rfc3744 section 5.6
        DAVACL::RESTRICTION_DENY_BEFORE_GRANT,
        DAVACL::RESTRICTION_GRANT_ONLY
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl', 'user_prop_acl_restrictions' ), array( $_SERVER['REQUEST_URI'] ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' );
    $obj->expects( $this->once() )
        ->method( 'user_prop_acl_restrictions' )
        ->will( $this->returnValue( $restrictions ) );
    
    $expected = <<<EOS

<D:required-principal>
<D:href>/path/to/principal1</D:href>
<D:href>/path/to/principal2</D:href>
<D:unauthenticated/>
<D:property><D:owner/></D:property>
<D:property><property-pointing-to-principal xmlns="http://test/"/></D:property>
</D:required-principal><D:deny-before-grant/><D:grant-only/>
EOS
    ;
    
    $this->assertSame( $expected, $obj->prop_acl_restrictions(), 'DAVACL_Resource::prop_acl_restrictions() should return the correct XML describing the ACL restrictions' );
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
    $return = array(
        'DAV: unbind',
        'DAV: write-content'
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl', 'user_prop_current_user_privilege_set' ), array( $_SERVER['REQUEST_URI'] ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' )
        ->will( $this->returnValue( array() ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_current_user_privilege_set' )
        ->will( $this->returnValue( $return ) );
    
    $this->assertSame( '<D:unbind/><D:write-content/>', $obj->prop_current_user_privilege_set(), 'DAVACL_Resource::prop_current_user_privilege_set() should the XML presentation of the current user\'s privilege set' );
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
  
  
  public function testPropname() {
    DAV::$LOCKPROVIDER = $this->getMock( 'DAV_Lock_Provider' );
    $expectedBasic = array(
        'DAV: acl' => false,
        'DAV: acl-restrictions' => false,
        'DAV: alternate-URI-set' => false,
        'DAV: current-user-principal' => false,
        'DAV: current-user-privilege-set' => false,
        'DAV: group' => false,
        'DAV: group-member-set' => false,
        'DAV: group-membership' => false,
        'DAV: inherited-acl-set' => false,
        'DAV: lockdiscovery' => true,
        'DAV: owner' => false,
        'DAV: principal-URL' => false,
        'DAV: principal-collection-set' => false,
        'DAV: resourcetype' => true,
        'DAV: supported-privilege-set' => false,
        'DAV: supported-report-set' => true,
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
    $stub = $this->getMock( 'DAVACL_Test_Principal', array( 'user_propname', 'user_prop_displayname' ), array( '/path' ) );
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
        'DAV: acl' => false,
        'DAV: acl-restrictions' => false,
        'DAV: alternate-URI-set' => false,
        'DAV: current-user-principal' => false,
        'DAV: current-user-privilege-set' => false,
        'DAV: group' => false,
        'DAV: group-member-set' => false,
        'DAV: group-membership' => false,
        'DAV: inherited-acl-set' => false,
        'DAV: lockdiscovery' => true,
        'DAV: owner' => false,
        'DAV: principal-URL' => false,
        'DAV: principal-collection-set' => false,
        'DAV: resourcetype' => true,
        'DAV: supported-privilege-set' => false,
        'DAV: supported-report-set' => true,
        'DAV: supportedlock' => true
    );
    ksort( $expected );
    $returned = $stub->propname();
    ksort( $returned );
    $this->assertSame( $expected, $returned, 'DAV_Resource::propname should return the correct values' );
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
    $eacl = array(
        array( true , array( 'DAV: read-acl' ) ),
        array( false, array( 'DAV: read-acl' ) ), // Is already denied by the previous row, so should not be granted now
        array( true , array( 'DAV: read-current-user-privilege-set' ) ), // This is denied by this row, so should not show up
        array( false, array( 'DAV: unbind' ) ), // Regular grant, so should show up
        array( false, array( 'DAV: write-content' ) ),
        array( true , array( 'DAV: write-content' ) ) // Is already granted by the previous row, so should not be denied by this row (and thus show up)
    );
    $obj = $this->getMock( 'DAVACL_Resource', array( 'user_prop_acl', 'effective_acl' ), array( $_SERVER['REQUEST_URI'] ) );
    $obj->expects( $this->any() )
        ->method( 'user_prop_acl' )
        ->will( $this->returnValue( array() ) );
    $obj->expects( $this->any() )
        ->method( 'effective_acl' )
        ->will( $this->returnValue( $eacl ) );
    
    $expected = array(
        'DAV: unbind',
        'DAV: write-content'
    );
    
    $this->assertSame( $expected, $obj->user_prop_current_user_privilege_set(), 'DAVACL_Resource::user_prop_current_user_privilege_set() should return the correct value' );
  }
  
  
  public function testUser_prop_group() {
    $this->assertNull( $this->obj->user_prop_group(), 'DAVACL_Resource::user_prop_group() default implementation should return null ' );
  }
  
  
  public function testUser_prop_inherited_acl_set() {
    $this->assertNull( $this->obj->user_prop_inherited_acl_set(), 'DAVACL_Resource::user_prop_inherited_acl_set() default implementation should return null ' );
  }
  
  
  public function testUser_prop_owner() {
    $this->assertNull( $this->obj->user_prop_owner(), 'DAVACL_Resource::user_prop_owner() default implementation should return null ' );
  }

} // class DAVACL_ResourceTest

// End of file
