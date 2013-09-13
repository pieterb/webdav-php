<?php
/**
 * Contains tests for the DAV_Request_ACL class
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
 * Contains tests for the DAV_Request_ACL class
 * @package DAV
 * @subpackage tests
 */
class DAV_Request_ACLTest extends PHPUnit_Framework_TestCase {
  
  /**
   * @var  DAV_TEST_Request_ACL  The object we will test
   */
  private $obj;
  
  
  public function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'ACL';
    $this->obj = DAV_Test_Request_ACL::inst();
  }
  

  public function testConstructor() {
    $ace1 = new DAVACL_Element_ace( DAVACL::PRINCIPAL_ALL, false, array( DAVACL::PRIV_READ ), false );
    $ace2 = new DAVACL_Element_ace( '/path/to/user', false, array( DAVACL::PRIV_ALL ), false );
    $this->assertEquals( array( $ace1, $ace2 ), $this->obj->aces, 'DAV_Request_ACL::__construct() should parse input XML correctly' );
  }


  public function testHandle() {
    DAV::$REGISTRY->setResourceClass( 'DAVACL_Test_Resource' );
    $readPriv = new DAVACL_Element_supported_privilege( 'DAV: read', false, 'Read permissions' );
    $allPriv = new DAVACL_Element_supported_privilege( 'DAV: all', false, 'Read permissions' );
    $allPriv->add_supported_privilege( $readPriv );
    DAV::$ACLPROVIDER->setSupportedPrivilegeSet( array( $allPriv ) );
    
    // First we expect the output of a succesful call to DAVACL_Test_Resource::set_acl() and the an error that not all privileges are supported
    $this->expectOutputString( <<<EOS
Array
(
    [0] => DAVACL_Element_ace Object
        (
            [principal] => DAV: all
            [invert] => 
            [deny] => 
            [privileges] => Array
                (
                    [0] => DAV: read
                )

            [protected] => 
            [inherited] => 
        )

    [1] => DAVACL_Element_ace Object
        (
            [principal] => /path/to/user
            [invert] => 
            [deny] => 
            [privileges] => Array
                (
                    [0] => DAV: all
                )

            [protected] => 
            [inherited] => 
        )

)
Content-Type: application/xml; charset="UTF-8"
HTTP/1.1 403 Forbidden
<?xml version="1.0" encoding="utf-8"?>
<D:error xmlns:D="DAV:">
<D:not-supported-privilege/>
</D:error>
EOS
    );

    $this->obj->handleRequest();
    
    // Not supported privileges should trigger an error
    DAV::$ACLPROVIDER->setSupportedPrivilegeSet( array() );
    $this->obj->handleRequest();
  }

} // class DAV_Request_ACLTest


class DAV_Test_Request_ACL extends DAV_Request_ACL {

  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }


  protected static function inputstring() {
    return <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<acl xmlns="DAV:">
  <ace>
    <principal>
      <all />
    </principal>
    <grant>
      <privilege>
        <read/>
      </privilege>
    </grant>
  </ace>
  <ace>
    <principal>
      <href><![CDATA[/path/to/user]]></href>
    </principal>
    <grant>
      <privilege>
        <all/>
      </privilege>
    </grant>
  </ace>
</acl>
EOS
    ;
  }

} // Class DAV_Test_Request_ACL

// End of file