<?php
/**
 * Contains the DAVACL_Test_ACL_Provider class
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
 * A mock for DAVACL_ACL_Provider
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
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

// End of file