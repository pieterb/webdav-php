<?php
/**
 * Contains tests for the DAV class
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
 * Contains tests for the DAV class
 * @package DAV
 * @subpackage tests
 */
class DAVTest extends PHPUnit_Framework_TestCase {


//  public function testAbs2uri() {
//  }
//

//  public function testDebug() {
//  }
//
//
//  public function testDetermine_client() {
//  }
//
//
//  public function testExpand() {
//  }
//
//
//  public function testForbidden() {
//  }
//
//
//  public function testHeader() {
//  }
//
//
//  public function testHttpDate() {
//  }
//
//
//  public function testIsValidURI() {
//  }
//
//
//  public function testParseURI() {
//  }
//
//
//  public function testRawurlencode() {
//  }
//
//
//  public function testRecursiveSerialize() {
//  }
//
//
//  public function testRedirect() {
//  }
//
//
//  public function testSlashify() {
//  }
//
//
//  public function testStatus_code() {
//  }
//
//
//  public function testUnslashify() {
//  }
//
//
//  public function testUrlbase() {
//  }
  
  
  public function testVar_dump() {
    // Extensive testing of this function would mostly mean extensive testing of PHP's native var_dump. So that's useless. Let's keep it easy
    $testvar = array( 'element 1', 'element2' );
    $this->assertEquals( "array(2) {\n  [0]=>\n  string(9) \"element 1\"\n  [1]=>\n  string(8) \"element2\"\n}\n",
                         DAV::var_dump( $testvar ),
                         'DAV::var_dump() should return the correct value for $testvar' );
  }


//  public function testXml_header() {
//  }
//
//
//  public function testXmlescape() {
//  }
//
//
//  public function testXmlunescape() {
//  }


} // End of DAVTest

// End of file