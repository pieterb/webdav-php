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
 * A mock for DAV_Lock_Provider
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAV_Test_Lock_Provider implements DAV_Lock_Provider {
  
  private $returnLock = false;

  public function returnLock( $trueOrFalse ) {
    $this->returnLock = (bool) $trueOrFalse;
  }

  public function getlock($path) {
    if ( !$this->returnLock ) {
      return null;
    }
    return new DAV_Element_activelock(
            array(
                'locktoken' => $this->setlock( null, null, null, null ),
                'lockroot' => $_SERVER['REQUEST_URI']
            )
    );
  }

  public function memberLocks($path) {
    if ( !$this->returnLock ) {
      return array();
    }
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