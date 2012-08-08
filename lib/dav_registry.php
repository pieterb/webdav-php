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
 * $Id: dav_registry.php 3349 2011-07-28 13:04:24Z pieterb $
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
interface DAV_Registry {
  
  
/**
 * @return DAV_Resource or null if the resource doesn't exist.
 * @param string $path The path to the resource, with or without trailing slash.
 */
public function resource($path);


/**
 * Called whenever a resource has been destroyed or moved.
 * @return void
 * @param string $path The unslashified path to the resource.
 */
public function forget($path);


/**
 * Puts a shallow lock on all resources identified by the paths.
 * It is guaranteed that the server will call {@link shallowUnlock()} after
 * calling this method, unless this method throws some exception.
 * @param array $write_paths array of unslashified paths
 * @param array $read_paths  array of unslashified paths
 */
public function shallowLock($write_paths, $read_paths);


/**
 * Unlocks all shallow locks, set with {@link shallowLock()}
 */
public function shallowUnlock();

  
}