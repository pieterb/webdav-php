<?php
/**
 * Contains tests for the DAV_Element_response class
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
 * Contains tests for the DAV_Element_response class
 * @package DAV
 * @subpackage tests
 */
class DAV_Element_responseTest extends PHPUnit_Framework_TestCase {
  
  public function testContructor() {
    $obj = new DAV_Element_Response( '/path' );
    $this->assertSame( '<D:response><D:href>/path</D:href></D:response>', str_replace( "\n", '', $obj->toXML() ) , 'DAV_Element_response: the path set by the constructor should be used in toXML()' );
    return $obj;
  }


  /**
   * @depends testContructor
   * @param   DAV_Element_response  $obj  The response object from previous tests
   * @return  DAV_Element_response        The response object for next tests
   */
  public function testSetProperty( $obj ) {
    $obj->setProperty( 'test://test/ empty_prop' );
    $this->assertSame( '<D:response><D:href>/path</D:href><D:propstat><D:prop><ns:empty_prop xmlns:ns="test://test/"/></D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>', str_replace( "\n", '', $obj->toXML() ) , 'DAV_Element_response: the path set by the constructor should be used in toXML()' );
    $obj->setProperty( 'test://test/ prop_with_value', '<![CDATA[Some piece of data]]>' );
    $this->assertSame( '<D:response><D:href>/path</D:href><D:propstat><D:prop><ns:empty_prop xmlns:ns="test://test/"/><ns:prop_with_value xmlns:ns="test://test/"><![CDATA[Some piece of data]]></ns:prop_with_value></D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>', str_replace( "\n", '', $obj->toXML() ) , 'DAV_Element_response: the path set by the constructor should be used in toXML()' );
    return $obj;
  }


  /**
   * @depends testSetProperty
   * @param   DAV_Element_response  $obj  The response object from previous tests
   * @return  void
   */
  public function testSetStatus( $obj ) {
    $obj->setStatus( 'test://test/ empty_prop', new DAV_Status( DAV::HTTP_CONFLICT ) );
    $obj->setStatus( 'test://test/ prop_with_value', new DAV_Status( DAV::HTTP_METHOD_NOT_ALLOWED ) );
    $this->assertSame( '<D:response><D:href>/path</D:href><D:propstat><D:prop><ns:empty_prop xmlns:ns="test://test/"/></D:prop><D:status>HTTP/1.1 409 Conflict</D:status></D:propstat><D:propstat><D:prop><ns:prop_with_value xmlns:ns="test://test/"><![CDATA[Some piece of data]]></ns:prop_with_value></D:prop><D:status>HTTP/1.1 405 Method Not Allowed</D:status></D:propstat></D:response>', str_replace( "\n", '', $obj->toXML() ) , 'DAV_Element_response: the path set by the constructor should be used in toXML()' );
  }

} // class DAV_Element_responseTest

// End of file