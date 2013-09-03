<?php
/**
 * Contains tests for the DAV_Multistatus class
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
 * Contains tests for the DAV_Multistatus class
 * @package DAV
 * @subpackage tests
 */
class DAV_MultistatusTest extends PHPUnit_Framework_TestCase {
  
  public function setUp() {
    $_SERVER['SCRIPT_NAME'] = '/path/to/script.php';
  }
  

  public function testInstAndActive() {
    // We can only check if it returns true after calling DAV_Multistatus::inst(), because testing for false would require us to be sure it has not been instantiated yet. Not possible in unit tests
    if ( ! DAV_Multistatus::active() ) {
      ob_start();
      DAV_Multistatus::inst();
      $startString = ob_get_contents();
      ob_end_clean();
      $this->assertSame( 'Content-Type: application/xml; charset="utf-8"HTTP/1.1 207 Multi-Status<?xml version="1.0" encoding="utf-8"?><D:multistatus xmlns:D="DAV:">', str_replace( "\n", '', $startString ), 'DAV_Multistatus::inst() should call the constructor and start the correct output' );
    }
    $this->assertTrue( DAV_Multistatus::active(), 'DAV_Multistatus::active() should return true after instantiation' );
  }


  /**
   * @depends testInstAndActive
   */
  public function testAddResponse() {
    $this->expectOutputString( <<<EOS

<D:response><D:href>/path</D:href>
</D:response>
EOS
    );
    $response = new DAV_Element_response( '/path' );
    DAV_Multistatus::inst()->addResponse( $response );
  }


  /**
   * @depends testAddResponse
   */
  public function testAddStatus() {
    $this->expectOutputString( '' );
    DAV_Multistatus::inst()->addStatus( '/path', DAV::HTTP_FORBIDDEN );
  }


  /**
   * @depends testAddStatus
   */
  public function testClose() {
    $this->expectOutputString( <<<EOS

<D:response>
<D:href>/path</D:href>
<D:status>HTTP/1.1 403 Forbidden</D:status>
</D:response>
</D:multistatus>
EOS
    );
    DAV_Multistatus::inst()->close();
  }
  
  
  /**
   * @depends testClose
   */
  public function testSecondClose() {
    // A second DAV_Multistatus::close() shouldn't do anything!
    $this->expectOutputString( '' );
    DAV_Multistatus::inst()->close();
  }

} // class DAV_MultistatusTest

// End of file