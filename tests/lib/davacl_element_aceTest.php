<?php
/**
 * Contains tests for the DAVACL_Element_ace class
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
 * Contains tests for the DAVACL_Element_ace class
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Element_aceTest extends PHPUnit_Framework_TestCase {
  
  private $obj;
  
  
  public function setUp() {
    $this->obj = new DAVACL_Element_ace( '/path/to/principal', true, array( 'NS1 privilege1', 'NS2 privilege 1' ), true, false, '/parent' );
  }


  public function testConstructor() {
    $this->assertSame( '/path/to/principal'                        , $this->obj->principal , 'DAVACL_Element_ace constructor should set the principal' );
    $this->assertSame( true                                        , $this->obj->invert    , 'DAVACL_Element_ace constructor should set invert' );
    $this->assertSame( array( 'NS1 privilege1', 'NS2 privilege 1' ), $this->obj->privileges, 'DAVACL_Element_ace constructor should set the privileges' );
    $this->assertSame( true                                        , $this->obj->deny      , 'DAVACL_Element_ace constructor should set deny' );
    $this->assertSame( false                                       , $this->obj->protected , 'DAVACL_Element_ace constructor should set protected' );
    $this->assertSame( '/parent'                                   , $this->obj->inherited , 'DAVACL_Element_ace constructor should set inherited' );
  }


  public function testToXML() {
    $this->assertSame( <<<EOS
<D:ace>
<D:invert><D:principal><D:href>/path/to/principal</D:href></D:principal></D:invert>
<D:deny><privilege1 xmlns="NS1"/><privilege xmlns="NS2"/></D:deny>
<D:inherited><D:href>/parent</D:href></D:inherited>
</D:ace>
EOS
            , $this->obj->toXML(), 'DAVACL_Element_ace::toXML() should return the correct XML string' );
  }


  public function testJsonConversion() {
    $json = DAVACL_Element_ace::aces2json( array( $this->obj ) );
    $objects = DAVACL_Element_ace::json2aces( $json );
    $expected = array( $this->obj );
    $expected[0]->inherited = null;
    $this->assertEquals( $expected, $objects, 'Json created with DAVACL_Element_ace::aces2json() should be converted back to the original objects by DAVACL_Element_ace::json2aces(), except for the inherited property, which should be null after deserialization' );
  }

} // class DAVACL_Element_aceTest

// End of file