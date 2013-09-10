<?php
/**
 * Contains tests for the DAV_Status class
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
 * Contains tests for the DAV_Status class
 * @package DAV
 * @subpackage tests
 */
class DAV_StatusTest extends PHPUnit_Framework_TestCase {

  public function testConstructor() {
    $status300 = new DAV_Status( 300, 'http://example.org/some/new/path' );
    $this->assertSame( 300, $status300->getCode(), 'DAV_Status should have the correct status code set' );
    $this->assertSame( 'http://example.org/some/new/path', $status300->location, 'DAV_Status should have the right location set when called with a 3XX status code' );

    $status400 = new DAV_Status( 400, 'some free error text' );
    $this->assertNull( $status400->location, 'DAV_Status should not have a location set when called with a 4XX status code' );
    $this->assertSame( 'some free error text', $status400->getMessage(), 'DAV_Status should have an error message when a 4XX code is used along with some text' );
    $this->assertSame( array(), $status400->conditions, 'DAV_Status should not have conditions set when a 4XX code is used along with some text' );

    $status409 = new DAV_Status( 409, array( DAV::COND_ALLOWED_PRINCIPAL => '<tag>someXML?</tag>') );
    $this->assertSame( '', $status409->getMessage(), 'DAV_Status should not have an error message when a 4XX code is used along with an array of preconditions' );
    $this->assertSame( array( DAV::COND_ALLOWED_PRINCIPAL => '<tag>someXML?</tag>' ), $status409->conditions, 'DAV_Status should have conditions set when constructed with them' );

    // 500 codes should also trigger an error
    $this->setExpectedException( 'PHPUnit_Framework_Error' );
    new DAV_Status( 500, 'some error' );
  }


  public function testOutput() {
    $_SERVER['SERVER_NAME'] = 'example.org';
    $_SERVER['SERVER_PORT'] = 80;
    $_SERVER['REQUEST_URI'] = '/resource.txt';

    $status300 = new DAV_Status( 300, 'http://example.org/some/new/path' );
    ob_start();
    $status300->output();
    $output300 = ob_get_clean();
    $this->assertSame(<<<EOS
Content-Type: text/plain; charset=US-ASCII
Location: http://example.org/some/new/path
HTTP/1.1 300 Multiple Choices
http://example.org/some/new/path
EOS
    , $output300, 'DAV_Status with code 300 should generate the right output');

    $status400 = new DAV_Status( 400, 'some free error text' );
    ob_start();
    $status400->output();
    $output400 = ob_get_clean();
    // Not the double status line; the first is a header, the second is part of the body!
    $this->assertSame(<<<EOS
Content-Type: text/plain; charset="UTF-8"
HTTP/1.1 400 Bad Request
HTTP/1.1 400 Bad Request
some free error text
EOS
    , $output400, 'DAV_Status with code 400 should generate the right output');

    $status409 = new DAV_Status( 409, array( DAV::COND_ALLOWED_PRINCIPAL => '<tag>someXML?</tag>') );
    ob_start();
    $status409->output();
    $output409 = ob_get_clean();
    // Not the double status line; the first is a header, the second is part of the body!
    $this->assertSame(<<<EOS
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 409 Conflict
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:allowed-principal><tag>someXML?</tag></D:allowed-principal>
</D:error>
EOS
    , $output409, 'DAV_Status with code 409 should generate the right output');
  }

} // class DAV_StatusTest

// End of file