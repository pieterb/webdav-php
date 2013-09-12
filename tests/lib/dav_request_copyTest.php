<?php
/**
 * Contains tests for the DAV_Request_COPY class
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
 * Contains tests for the DAV_Request_COPY class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_COPYTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @var  DAV_Request_COPY  The object we will test
   */
  private $obj;
  
  
  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'COPY';
    $_SERVER['HTTP_DEPTH'] = 'infinity';
    $_SERVER['HTTP_DESTINATION'] = '/new/destination';
    $this->obj = DAV_Request::inst();;
  }


  public function testDepth() {
    $_SERVER['HTTP_DEPTH'] = '0';
    $this->assertEquals( '0', $this->obj->depth(), 'DAV_Request_COPY::depth() should return the Depth header correctly' );
    unset( $_SERVER['HTTP_DEPTH'] );
    $this->assertEquals( 'infinity', $this->obj->depth(), 'DAV_Request_COPY::depth() should return \'infinity\' if the Depth header is not set' );
  }


  protected function testHandle() {
    $this->assertTrue(false, 'Write assertments for this test');
    // Can't move the root collection
    // Assert proper Depth: header value
    // Check: Can't move a collection to one of its members.
    // Copy to an external URI?
    // Check: Won't move a resource to one of its parents.
    // Unable to COPY to unexisting destination collection
    //  if ($this->overwrite()) {
    //    DAV_Request_DELETE::delete($destinationResource);
    //  else
    //    throw new DAV_Status(DAV::HTTP_PRECONDITION_FAILED);
    // if ($this instanceof DAV_Request_MOVE) {
    // else
    //   $this->copy_recursively( $resource, $destination );
    // Check: output (multistatus) is correct?
  }

} // class DAV_Request_COPYTest

// End of file