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


  public function testCurrent_user_principals() {
    $expected = array(
      'DAV: all' => 'DAV: all',
      '/path/to/current/user' => '/path/to/current/user',
      '/path/to/group' => '/path/to/group',
      'DAV: authenticated' => 'DAV: authenticated'
    );
    $this->assertSame( $expected, $this->obj->current_user_principals(), 'DAVACL_Resource::current_user_principals() should return the complete list of principals the current user maps to' );
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
    $this->assertSame( 'a', $this->obj->prop_acl_restrictions(), 'DAVACL_Resource::prop_acl_restrictions() should return the correct value' );
  }

} // class DAVACL_ResourceTest

// End of file
