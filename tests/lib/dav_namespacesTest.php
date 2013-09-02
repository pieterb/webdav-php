<?php
/**
 * Contains tests for the DAV_Namespaces class
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
 * Contains tests for the DAV_Namespaces class
 * @package DAV
 * @subpackage tests
 */
class DAV_NamespacesTest extends PHPUnit_Framework_TestCase {

  const DAV = 'DAV:';
  const XML = 'http://www.w3.org/XML/1998/namespace';


  /**
   * Add an XML namespace to the set of namespaces
   * @param $namespaceURI The URI of the namespace you want to get a prefix for.
   * @return string a prefix, including the trailing colon.
   */
  public function testPrefixAndToXML() {
    $obj = new DAV_Namespaces();
    $this->assertEquals( '', $obj->toXML(), 'Before adding additional namespaces, no namespaces should be returned' );
    $obj->prefix( 'tests://test/' );
    $obj->prefix( 'tests://more_tests/' );
    $obj->prefix( 'tests://test/' ); // We add this twice to test if it is only returned once
    $this->assertEquals( ' xmlns:ns1="tests://test/" xmlns:ns2="tests://more_tests/"', $obj->toXML(), 'After adding additional namespaces, the two additional namespaces should be returned' );
  }

} // class DAV_NamespacesTest

// End of file