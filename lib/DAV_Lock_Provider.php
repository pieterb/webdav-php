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
 * $Id: dav_lock_provider.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Library users must instantiate this interface.
 * @internal
 * @package DAV
 */
interface DAV_Lock_Provider {


/**
 * All locks on members of $path.
 * @param string $path
 * @return array all locks below $path, indexed by token.
 */
public function memberLocks($path);


/**
 * @param string $path
 * @return DAV_Element_activelock the lock affecting $path, or null.
 */
public function getlock($path);


/**
 * @param string $lockroot a path
 * @param string $depth DAV::DEPTH_0 or DAV::DEPTH_INF
 * @param string $owner XML fragment
 * @param array $timeout array of timeouts as seconds remaining or 0.
 * @return string locktoken
 */
public function setlock($lockroot, $depth, $owner, $timeout);


/**
 * @param string $path
 * @param string $locktoken the lock token
 * @param array $timeout array of timeouts as seconds remaining or 0.
 * @return bool true if the token was refreshed, otherwise false.
 */
public function refresh($path, $locktoken, $timeout);


/**
 * @param string $path
 * @return bool success indicator
 */
public function unlock($path);


}
