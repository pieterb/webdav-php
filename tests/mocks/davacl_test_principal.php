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
 * A mock for DAVACL_Principal
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Test_Principal extends DAVACL_Test_Resource implements DAVACL_Principal {

  public function user_prop_alternate_uri_set() {
    return array();
  }

  public function user_prop_group_member_set() {
    return array();
  }

  public function user_prop_group_membership() {
    if ( $this->path !== '/path/to/group' ) {
      return array( '/path/to/group' );
    } else {
      return array();
    }
  }

  public function user_prop_principal_url() {
    return $this->path;
  }

  public function user_set_group_member_set($set) {

  }

} // Class DAVACL_Test_Principal

// End of file