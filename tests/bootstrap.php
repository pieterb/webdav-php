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

require_once( dirname( dirname( __FILE__ ) ) . '/lib/bootstrap.php' );
DAV::$testMode = true; // Turn on test mode, so headers won't be sent, because sending headers won't work as all tests are run from the commandline

$_SERVER = array();
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SCRIPT_NAME'] = 'bootstrap.php'; // Strange enough, PHPunit seems to use this, so let's set it to some value
$_SERVER['SERVER_NAME'] = 'example.org';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/path';
$_SERVER['REQUEST_METHOD'] = 'GET';


/**
 * A copy of the key-value cache so the real DAV_Cache won't be loaded
 * 
 * This copy doesn't function and will always return NULL for each value. This
 * is useful for testing purposes
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAV_Cache {
  
  /**
   * @var  DAV_Cache  All created caches
   */
  private static $instance = null;
  
  
  /**
   * The constructor is private as part of the singleton pattern
   */
  private function __construct(){
  }
  
  
  /**
   * Returns always the only instance of this class
   * 
   * @param   string     $cacheName  The name of the cache
   * @return  DAV_Cache              The requested cache
   */
  public static function inst( $cacheName ) {
    if ( is_null( self::$instance ) ) {
      $class = get_called_class();
      self::$instance = new $class();
    }
    return self::$instance;
  }

  /**
   * Always returns NULL, as this class doesn't actually cache anything
   * 
   * @param   string  $key  The key to return the value for
   * @return  mixed         The value from cache or NULL when the key doesn't exist
   */
  public function get( $key ) {
    return null;
  }

  /**
   * Doesn't do anything
   * 
   * @param   string  $key    The key for which to set the value
   * @param   mixed   $value  The value to set
   * @return  void
   */
  public function set( $key, $value ) {
  }

} // DAV_Cache


class DAV_Test_Registry implements DAV_Registry {
  
  private $resourceClass = 'DAV_Resource';
  
  
  public function setResourceClass( $resource ) {
    $this->resourceClass = $resource;
  }
  

  public function resource( $path ) {
    if ( is_array( $this->resourceClass ) ) {
      switch ( count( $this->resourceClass ) ) {
        case 0:
          $resourceClass = null;
          break;
        case 1:
          $this->resourceClass = $this->resourceClass[0];
          $resourceClass = $this->resourceClass;
          break;
        default:
          $resourceClass = array_shift( $this->resourceClass );
        break;
      }
    }else{
      $resourceClass = $this->resourceClass;
    }
    if ( is_null( $resourceClass ) ) {
      return null;
    }elseif ( $resourceClass instanceof DAV_Resource ) {
      return $resourceClass;
    }
    return new $resourceClass( $path );
  }


  public function forget( $path ) {
  }


  public function shallowLock( $write_paths, $read_paths ) {
  }


  public function shallowUnlock() {
  }
  
} // DAV_Test_Registry
DAV::$REGISTRY = new DAV_Test_Registry();


class DAVACL_Test_ACL_Provider implements DAVACL_ACL_Provider {
  
  public function user_prop_acl_restrictions() {
    return array();
  }

  public function user_prop_current_user_principal() {
    return '/path/to/current/user';
  }

  public function user_prop_principal_collection_set() {
    return array( '/path/to/current/user' );
  }
  
  
  private $supportedPrivilegeSet = array();
  
  
  public function setSupportedPrivilegeSet( $supportedPrivilegeSet ) {
    $this->supportedPrivilegeSet = $supportedPrivilegeSet;
  }
  

  public function user_prop_supported_privilege_set() {
    return $this->supportedPrivilegeSet;
  }
  
} // DAVACL_Test_ACL_Provider
DAV::$ACLPROVIDER = new DAVACL_Test_ACL_Provider();


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

} // Class DAVACL_Test_Resource


class DAV_Test_Lock_Provider implements DAV_Lock_Provider {

  public function getlock($path) {
    return new DAV_Element_activelock(
            array(
                'locktoken' => $this->setlock( null, null, null, null ),
                'lockroot' => $_SERVER['REQUEST_URI']
            )
    );
  }

  public function memberLocks($path) {
    return array( $this->getlock( $path ) );
  }

  public function refresh($path, $locktoken, $timeout) {
    return true;
  }

  public function setlock($lockroot, $depth, $owner, $timeout) {
    return 'urn:uuid:e71d4fae-5dec-22d6-fea5-00a0c91e6be4';
  }

  public function unlock($path) {
    return true;
  }

} // Class DAV_Test_Lock_Provider

// End of file