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
 * A mock for DAV_Registry
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
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

// End of file