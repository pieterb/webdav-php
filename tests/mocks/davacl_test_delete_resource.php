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
 * A mock for DAVACL_Test_Collection
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Test_Delete_Resource extends DAVACL_Test_Collection {

  private $children = array();
  private $position = 0;


  public function __construct( $path ) {
    parent::__construct( $path );
    if ( $path === '/path/to/resource/' ) {
      $this->children = array( 'subdir1/', 'subdir2/', 'subdir3/' );
    }elseif ( $path === '/path/to/resource/subdir1/' ) {
      $this->children = array( 'subfile1', 'subfile2' );
    }
  }


  public function rewind() {
    $this->position = 0;
  }

  public function current() {
    return $this->children[ $this->position ];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    $this->position++;
  }

  public function valid() {
    return isset( $this->children[ $this->position ] );
  }

} // Class DAVACL_Test_Delete_Resource

// End of file