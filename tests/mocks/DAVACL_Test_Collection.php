<?php
/**
 * Contains the DAVACL_Test_Collection class
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
 * A mock for DAV_Collection
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Test_Collection extends DAVACL_Test_Resource implements DAV_Collection {

  private $expectedPrivileges = null;


  public function setExpectedPrivileges( $privileges ) {
    $this->expectedPrivileges = $privileges;
  }


  public function assert( $privileges ) {
    if ( ! is_array( $privileges ) ) {
      $privileges = array((string)$privileges);
    }
    if ( ! is_null( $this->expectedPrivileges ) && $this->expectedPrivileges != $privileges ) {
//    if ( count( $privileges ) != 1 || $privileges[0] !== DAVACL::PRIV_WRITE_ACL ) {
      throw new Exception( "DAVACL_Test_Resource::assert() called with wrong parameters!" );
    }
    return true;
  }


  public function set_acl( $acl ) {
    print_r( $acl );
  }


  public function user_prop_acl() {
  }

  public function create_member($name) {
  }

  public function method_DELETE( $name ) {
    print( "DAVACL_Test_Collection::method_DELETE() called for " . $this->path . " and parameter " . $name . "\n" );
  }

  public function method_MKCOL( $name ) {
    print( "DAVACL_Test_Collection::method_MKCOL() called for " . $this->path . " and parameter " . $name . "\n" );
  }

  public function method_MOVE( $member, $destination ) {
    print( "DAVACL_Test_Collection::method_MOVE() called for " . $this->path . " and parameters " . $member . " - " . $destination . "\n" );
  }

  private $position = 0;
  private $array = array(
      "child1",
      "child2"
  );

  function rewind() {
    $this->position = 0;
  }

  function current() {
    return $this->array[$this->position];
  }

  function key() {
    return $this->position;
  }

  function next() {
    ++$this->position;
  }

  function valid() {
    return isset($this->array[$this->position]);
  }

} // Class DAVACL_Test_Collection

// End of file