<?php
/**
 * Sets up an environment to emulate a webserver environment
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
 * A mock for DAVACL_Resource
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Test_Resource extends DAVACL_Resource {

  private $expectedPrivileges = null;


  public function collection() {
    if ( $this->path === '/' ) {
      return null;
    }
    return new DAVACL_Test_Collection( dirname( $this->path ) );
  }


  public function setExpectedPrivileges( $privileges ) {
    $this->expectedPrivileges = $privileges;
  }


  public function assert( $privileges ) {
    if ( ! is_array( $privileges ) ) {
      $privileges = array((string)$privileges);
    }
    if ( ! is_null( $this->expectedPrivileges ) && $this->expectedPrivileges != $privileges ) {
      throw new Exception( "DAVACL_Test_Resource::assert() called with wrong parameters!" );
    }
    return true;
  }


  public function method_COPY( $path ) {
    print( "DAVACL_Test_Resource::method_COPY_external() called for " . $this->path . " and parameter " . $path . "\n" );
  }


  public function method_COPY_external( $destination, $overwrite ) {
    print( "DAVACL_Test_Resource::method_COPY_external() called for " . $this->path . " and parameters " . $destination . " - " . $overwrite . "\n" );
  }


  public function method_PROPPATCH( $propname, $value = null ) {
    print( "DAVACL_Test_Resource::method_PROPPATCH() called for " . $this->path . " and parameters " . $propname . " - " . ( is_null( $value ) ? 'NULL' : "'" . $value . "'" ) . "\n" );
  }


  public function method_PUT( $stream ){
    print( "DAVACL_Test_Resource::method_PUT() called for " . $this->path . "\n" );
  }


  public function method_PUT_range( $stream ){
    print( "DAVACL_Test_Resource::method_PUT_range() called for " . $this->path . "\n" );
  }


  public function property_priv_write($properties) {
    $retval = parent::property_priv_write($properties);
    if ( isset( $retval['test: forbidden_to_write'] ) ) {
      $retval['test: forbidden_to_write'] = false;
    }
    return $retval;
  }


  public function set_acl( $acl ) {
    print_r( $acl );
  }


  public function storeProperties() {
    print( "DAVACL_Test_Resource::storeProperties() called for " . $this->path . "\n" );
  }


  public function user_prop_acl() {
  }

} // Class DAVACL_Test_Resource

// End of file