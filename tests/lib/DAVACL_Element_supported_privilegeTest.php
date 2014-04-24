<?php
/**
 * Contains tests for the DAVACL_Element_supported_privilege class
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
 * Contains tests for the DAVACL_Element_supported_privilege class
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Element_supported_privilegeTest extends PHPUnit_Framework_TestCase {
  
  private $obj;
  

  public function setUp() {
    $this->obj = new DAVACL_Element_supported_privilege( 'NS1 privilege1', true, 'Can I do something?' );
  }


  public function testGetNamespace() {
    $this->assertSame( 'NS1', $this->obj->getNamespace() );
  }


  public function testGetName() {
    $this->assertSame( 'privilege1', $this->obj->getName() );
  }


  public function testToXML() {
    $this->assertSame( <<<EOS
<D:supported-privilege xmlns:ns1="NS1">
<D:privilege><ns1:privilege1/></D:privilege>
<D:abstract/>
<D:description>Can I do something?</D:description>
</D:supported-privilege>
EOS
            , $this->obj->toXML(), 'DAVACL_Element_supported_privilege::toXML() should generate the correct XML output' );
  }


  public function testAdd_supported_privilege() {
    $priv2 = new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'Can I do something else?' );
    $this->obj->add_supported_privilege( $priv2 );
    $priv3 = new DAVACL_Element_supported_privilege( 'NS2 privilege1', false, 'May I do something?' );
    $this->obj->add_supported_privilege( $priv3 );
    $priv4 = new DAVACL_Element_supported_privilege( 'NS2 privilege2', true, 'May I do something else?' );
    $this->obj->add_supported_privilege( $priv4 );
    $this->assertSame( <<<EOS
<D:supported-privilege xmlns:ns1="NS1" xmlns:ns2="NS2">
<D:privilege><ns1:privilege1/></D:privilege>
<D:abstract/>
<D:description>Can I do something?</D:description>
<D:supported-privilege>
<D:privilege><ns1:privilege2/></D:privilege>
<D:description>Can I do something else?</D:description>
</D:supported-privilege>
<D:supported-privilege>
<D:privilege><ns2:privilege1/></D:privilege>
<D:description>May I do something?</D:description>
</D:supported-privilege>
<D:supported-privilege>
<D:privilege><ns2:privilege2/></D:privilege>
<D:abstract/>
<D:description>May I do something else?</D:description>
</D:supported-privilege>
</D:supported-privilege>
EOS
            , $this->obj->toXML(), 'DAVACL_Element_supported_privilege::add_supported_privilege() should add supported privileges to the generated XML output' );
  }


  public function testFlatten() {
    $priv2 = new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'Can I do something else?' );
    $this->obj->add_supported_privilege( $priv2 );
    $expected = array (
      'NS1 privilege2' => array (
          'children' => array ( 'NS1 privilege2' ),
          'abstract' => false
      ),
      'NS1 privilege1' => array (
          'children' => array ( 'NS1 privilege1', 'NS1 privilege2' ),
          'abstract' => true
      )
    );
    $this->assertSame( $expected, DAVACL_Element_supported_privilege::flatten( array( $this->obj ) ), 'DAVACL_Element_supported_privilege::flatten() should return a correctly flattened array' );
  }


  public function testIsAggregatePrivilege() {
    $this->assertFalse( $this->obj->isAggregatePrivilege() );

    $this->obj->add_supported_privilege( new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'useless privilege' ) );
    $this->assertTrue( $this->obj->isAggregatePrivilege() );
  }


  public function testGetSubPrivileges() {
    $this->assertSame( array(), $this->obj->getSubPrivileges() );

    $subPrivilege = new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'useless privilege' );
    $this->obj->add_supported_privilege( $subPrivilege );
    $this->assertSame( array( $subPrivilege ), $this->obj->getSubPrivileges() );
  }


  public function testFindSubPrivilege() {
    $this->assertSame( $this->obj, $this->obj->findSubPrivilege( 'NS1 privilege1' ) );

    $subPrivilege = new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'useless privilege' );
    $this->obj->add_supported_privilege( $subPrivilege );
    $this->assertSame( $subPrivilege, $this->obj->findSubPrivilege( 'NS1 privilege2' ) );

    $subSubPrivilege = new DAVACL_Element_supported_privilege( 'NS1 privilege3', false, 'useless privilege' );
    $subPrivilege->add_supported_privilege( $subSubPrivilege );
    $this->assertSame( $subSubPrivilege, $this->obj->findSubPrivilege( 'NS1 privilege3' ) );

    $this->assertNull( $this->obj->findSubPrivilege( 'NS1 privilege4' ) );
  }


  public function testGetNonAggregatePrivileges(){
    $this->assertSame( array( $this->obj ), $this->obj->getNonAggregatePrivileges() );

    $subPrivilege = new DAVACL_Element_supported_privilege( 'NS1 privilege2', false, 'useless privilege' );
    $this->obj->add_supported_privilege( $subPrivilege );
    $this->assertSame( array( $subPrivilege ), $this->obj->getNonAggregatePrivileges() );
    
    $subSubPrivilege = new DAVACL_Element_supported_privilege( 'NS1 privilege3', false, 'useless privilege' );
    $subPrivilege->add_supported_privilege( $subSubPrivilege );
    $this->assertSame( array( $subSubPrivilege ), $this->obj->getNonAggregatePrivileges() );

    $subSubPrivilege2 = new DAVACL_Element_supported_privilege( 'NS1 privilege4', false, 'useless privilege' );
    $subPrivilege->add_supported_privilege( $subSubPrivilege2 );
    $twoNonAggregatedPrivileges = $this->obj->getNonAggregatePrivileges();
    $this->assertCount( 2, $twoNonAggregatedPrivileges );
    $this->assertContains( $subSubPrivilege, $twoNonAggregatedPrivileges);
    $this->assertContains( $subSubPrivilege2, $twoNonAggregatedPrivileges);
  }

} // class DAVACL_Element_supported_privilegeTest

// End of file