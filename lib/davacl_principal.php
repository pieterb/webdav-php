<?php

/*·************************************************************************
 * Copyright ©2007-2011 Pieter van Beek, Almere, The Netherlands
 * 		    <http://purl.org/net/6086052759deb18f4c0c9fb2c3d3e83e>
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
 * $Id: davacl_principal.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */

/**
 * Interface for all principals.
 * @package DAVACL
 */
interface DAVACL_Principal {
  

const RESOURCETYPE = '<D:principal/>';


/**
 * @return array of URIs
 */
public function user_prop_alternate_uri_set();


/**
 * @return string path
 */
public function user_prop_principal_url();


/**
 * @return array of paths
 */
public function user_prop_group_member_set();


/**
 * @param array $set an array of paths
 * @see DAVACL_Resource
 */
public function user_set_group_member_set($set);


/**
 * @return array of paths
 */
public function user_prop_group_membership();


}
