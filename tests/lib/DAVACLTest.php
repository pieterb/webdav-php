<?php
/**
 * Contains tests for the DAVACL class
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
 * Contains tests for the DAVACL class
 * @package DAV
 * @subpackage tests
 */
class DAVACLTest extends PHPUnit_Framework_TestCase {

  public function testParse_hrefs() {
    $_SERVER['SERVER_NAME'] = 'example.com';
    $_SERVER['SERVER_PORT'] = 80;
    $this->assertSame( 0, count( DAVACL::parse_hrefs( 'http://example.com' )->URIs ), 'DAVACL::parse_hrefs() should not parse the URL if it is not an XML piece' );
    $this->assertSame( '/some/path/to/somewhere', DAVACL::parse_hrefs( '<D:href>http://example.com/some/path/to/somewhere</D:href>' )->URIs[0], 'DAVACL::parse_hrefs() should not change the URL if it is not an XML piece' );
  }

} // End of DAVACLTest

// End of file