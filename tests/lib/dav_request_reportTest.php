<?php
/**
 * Contains tests for the DAV_Request_REPORT class
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
 * Contains tests for the DAV_Request_REPORT class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_REPORTTest extends PHPUnit_Framework_TestCase {

  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'REPORT';
    DAV::$REGISTRY = new DAV_Test_Registry();
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
  }


  public function testConstructorEmptyBody() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_REPORT::setInputstring( '' );
    DAV_Test_Request_REPORT::inst();
  }


  public function testConstructorWrongXML() {
    $this->setExpectedException( 'DAV_Status', null, 400 );
    DAV_Test_Request_REPORT::setInputstring( 'something that is not XML' );
    DAV_Test_Request_REPORT::inst();
  }
  
  
  public function testParse_expand_property() {
    DAV_Test_Request_REPORT::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:expand-property xmlns:D="DAV:">
  <D:property name="version-history">
    <D:property name="version-set">
      <D:property name="creator-displayname"/>
      <D:property name="activity-set"/>
    </D:property>
  </D:property>
</D:expand-property>
EOS
    );
    $obj = DAV_Test_Request_REPORT::inst();
    $expected = array(
        'DAV: version-history' => array(
            'DAV: version-set' => array(
                'DAV: creator-displayname' => array(),
                'DAV: activity-set' => array()
            )
        )
    );
    
    $this->assertSame( 'expand_property', $obj->type  , 'DAV_Request_REPORT should recognize expand-property as a type' );
    $this->assertSame( $expected        , $obj->entity, 'DAV_Request_REPORT for DAV: expand-property should parse XML correctly' );
    
    return $obj;
  }


  public function testParse_acl_principal_prop_set() {
    DAV_Test_Request_REPORT::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?> 
<D:acl-principal-prop-set xmlns:D="DAV:"> 
  <D:prop> 
    <D:displayname/> 
  </D:prop> 
</D:acl-principal-prop-set> 
EOS
    );
    $obj = DAV_Test_Request_REPORT::inst();
    
    $this->assertSame( 'acl_principal_prop_set'   , $obj->type  , 'DAV_Request_REPORT should recognize acl-principal-prop-set as a type' );
    $this->assertSame( array( 'DAV: displayname' ), $obj->entity, 'DAV_Request_REPORT for DAV: acl-principal-prop-set should parse XML correctly' );
    
    return $obj;
  }
  
  
  public function testParse_principal_property_search() {
    DAV_Test_Request_REPORT::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<D:principal-property-search xmlns:D="DAV:">
  <D:property-search>
    <D:prop>
      <D:displayname/>
    </D:prop>
    <D:match>doE</D:match>
  </D:property-search>
  <D:prop xmlns:B="http://www.example.com/ns/">
    <D:displayname/>
  </D:prop>
</D:principal-property-search>
EOS
    );
    $obj = DAV_Test_Request_REPORT::inst();
    $expected = array(
        'match' => array( 'DAV: displayname' => array( 'doE' ) ),
        'prop' => array( 'DAV: displayname' )
    );
    
    $this->assertSame( 'principal_property_search', $obj->type  , 'DAV_Request_REPORT should recognize principal-property-search as a type' );
    $this->assertSame( $expected, $obj->entity, 'DAV_Request_REPORT for DAV: principal-property-search should parse XML correctly' );
    
    return $obj;
  }
  

  public function testParse_principal_search_property_set() {
    DAV_Test_Request_REPORT::setInputstring( <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<principal-search-property-set xmlns="DAV:" />
EOS
    );
    $obj = DAV_Test_Request_REPORT::inst();
    $this->assertSame( 'principal_search_property_set', $obj->type  , 'DAV_Request_REPORT should recognize principal-search-property-set as a type' );
    
    return $obj;
  }
  
  
  /**
   * @depends  testParse_expand_property
   * @param    DAV_Test_Request_REPORT    $obj
   * @return   void
   */
  public function testHandle_expand_property( $obj ) {
    $this->expectOutputString( <<<EOS

<D:response><D:href>/path</D:href>
<D:propstat><D:prop>
<D:version-history/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
EOS
    );
    $obj->handleRequest();
  }


  /**
   * @depends  testParse_acl_principal_prop_set
   * @param    DAV_Test_Request_REPORT           $obj
   * @return   void
   */
  public function testHandle_acl_principal_prop_set( $obj ) {
    $acl = array( new DAVACL_Element_ace( '/path/to/user', false, array( DAVACL::PRIV_ALL ), false ) );
    $acl[] = new DAVACL_Element_ace( '/path/to/other/user', false, array( DAVACL::PRIV_ALL ), false );
    $acl[] = new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, array( DAVACL::PRIV_ALL ), false );
    $resource = $this->getMock( 'DAVACL_Test_Resource', array( 'user_prop_acl' ), array( $_SERVER['REQUEST_URI'] ) );
    $resource->expects( $this->any() )
             ->method( 'user_prop_acl' )
             ->will( $this->returnValue( $acl ) );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS

<D:response><D:href>/path/to/user</D:href>
<D:propstat><D:prop>
<D:displayname/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
<D:response><D:href>/path/to/other/user</D:href>
<D:propstat><D:prop>
<D:displayname/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
EOS
    );
    $obj->handleRequest();
  }
  
  
  /**
   * @depends  testParse_principal_property_search
   * @param    DAV_Test_Request_REPORT              $obj
   * @return   void
   */
  public function testHandle_principal_property_search( $obj ) {
    $resource = $this->getMock( 'DAVACL_Test_Resource', array( 'report_principal_property_search' ), array( $_SERVER['REQUEST_URI'] ) );
    $resource->expects( $this->any() )
             ->method( 'report_principal_property_search' )
             ->will( $this->returnValue( array( '/path/to/principal', '/path/to/other/principal' ) ) );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS

<D:response><D:href>/path/to/principal</D:href>
<D:propstat><D:prop>
<D:displayname/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
<D:response><D:href>/path/to/other/principal</D:href>
<D:propstat><D:prop>
<D:displayname/>
</D:prop>
<D:status>HTTP/1.1 200 OK</D:status>
</D:propstat>
</D:response>
EOS
    );
    $obj->handleRequest();
  }
  

  /**
   * @depends  testParse_principal_search_property_set
   * @param    DAV_Test_Request_REPORT                  $obj
   * @return   void
   */
  public function testHandle_principal_search_property_set( $obj ) {
    $returnValue = array(
        'DAV: displayname'    => 'The displayname of the principal',
        'DAV: getcontenttype' => 'The content type of the principal'
    );
    $resource = $this->getMock( 'DAVACL_Test_Resource', array( 'report_principal_search_property_set' ), array( $_SERVER['REQUEST_URI'] ) );
    $resource->expects( $this->any() )
             ->method( 'report_principal_search_property_set' )
             ->will( $this->returnValue( $returnValue ) );
    DAV::$REGISTRY->setResourceClass( $resource );
    $this->expectOutputString( <<<EOS
<?xml version="1.0" encoding="utf-8"?>
<D:principal-search-property-set xmlns:D="DAV:">
<D:principal-search-property><D:prop>
<D:displayname/><D:description xml:lang="en">The displayname of the principal</D:description></D:principal-search-property>
<D:principal-search-property><D:prop>
<D:getcontenttype/><D:description xml:lang="en">The content type of the principal</D:description></D:principal-search-property>
</D:principal-search-property-set>
EOS
    );
    $obj->handleRequest();
  }

} // Class DAV_Request_REPORTTest


class DAV_Test_Request_REPORT extends DAV_Request_REPORT {

  /**
   * @return \DAV_Test_Request_REPORT
   */
  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }


  private static $inputstring = '';


  public static function setInputstring( $inputstring ) {
    self::$inputstring = $inputstring;
  }


  protected static function inputstring() {
    return self::$inputstring;
  }

} // Class DAV_Test_Request_REPORT

// End of file