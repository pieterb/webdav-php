<?php
/**
 * Contains tests for the DAV_Element_href class
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
 * Contains tests for the DAV_Element_href class
 * @package DAV
 * @subpackage tests
 */
class DAV_Element_hrefTest extends PHPUnit_Framework_TestCase {

  public function testConstructor() {
    $objEmpty = new DAV_Element_href();
    $this->assertCount( 0                , $objEmpty->URIs , 'DAV_Element_href->URIs should be empty when created without constructor parameters' );
    $objString = new DAV_Element_href( '/path' );
    $this->assertEquals( array( '/path' ), $objString->URIs, 'DAV_Element_href->URIs be an array with one element when the constructor is called with a string as parameter' );
    $expectedArray = array( '/path1', '/path2' );
    $objArray = new DAV_Element_href( $expectedArray );
    $this->assertEquals( $expectedArray  , $objArray->URIs , 'DAV_Element_href->URIs should contain the right values when the constructor is called with an array as parameter' );
  }


  public function testAddURI() {
    $obj = new DAV_Element_href();
    $obj->addURI( '/path1' );
    $this->assertEquals( array( '/path1' )                    , $obj->URIs , 'DAV_Element_href->URIs should contain one element when DAV_Element_href::addURI() is called once' );
    $obj->addURI( '/path2' );
    $this->assertEquals( array( '/path1', '/path2' )          , $obj->URIs , 'DAV_Element_href->URIs should contain two elements when DAV_Element_href::addURI() is called twce' );
    $obj->addURI( '/path3' );
    $this->assertEquals( array( '/path1', '/path2', '/path3' ), $obj->URIs , 'DAV_Element_href->URIs should contain three elements when DAV_Element_href::addURI() is called thrice' );
  }


  public function testToString() {
    $obj = new DAV_Element_href( array( '/path1', '/path2' ) );
    $this->assertEquals( '<D:href>/path1</D:href><D:href>/path2</D:href>', str_replace( "\n", '', $obj->__toString() ), 'DAV_Element_href::__toString() should return a correct piece of XML' );
  }

} // class DAV_Element_hrefTest

// End of file