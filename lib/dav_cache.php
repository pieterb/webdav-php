<?php
/**
 * Contains the DAV_Cache class
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
 */

/**
 * Key-value cache
 * 
 * @internal
 * @package DAV
 */
class DAV_Cache {
  
  /**
   * @var  DAV_Cache  All created caches
   */
  private static $instances = array();
  
  
  /**
   * The constructor is private as part of the singleton pattern
   */
  private function __construct(){
  }
  
  
  /**
   * Returns a specific cache and creates it first if needed
   * 
   * @param   string     $cacheName  The name of the cache
   * @return  DAV_Cache              The requested cache
   */
  public static function inst( $cacheName ) {
    if ( ! isset( self::$instances[ $cacheName ] ) ) {
      $class = get_called_class();
      self::$instances[ $cacheName ] = new $class();
    }
    return self::$instances[ $cacheName ];
  }

  /**
   * @var  array  The internal cache
   */
  private $cache = array();

  /**
   * Get a value from cache
   * 
   * @param   string  $key  The key to return the value for
   * @return  mixed         The value from cache or NULL when the key doesn't exist
   */
  public function get( $key ) {
    if ( isset( $this->cache[ $key ] ) ) {
      return $this->cache[ $key ];
    }else{
      return null;
    }
  }

  /**
   * Set a value in cache
   * 
   * @param   string  $key    The key for which to set the value
   * @param   mixed   $value  The value to set
   * @return  void
   */
  public function set( $key, $value ) {
    $this->cache[ $key ] = $value;
  }

} // DAV_Cache

// End of file