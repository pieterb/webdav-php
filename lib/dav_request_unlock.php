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
 * $Id: dav_request_unlock.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class for parsing LOCK request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_UNLOCK extends DAV_Request {
    
    
/**
 * @var string XML fragment
 */
public $locktoken;


/**
 * Enter description here...
 *
 * @param string $path
 * @throws DAV_Status
 */
protected function __construct()
{
  parent::__construct();
  
  // Parse the Timeout: request header:
  if ( !isset( $_SERVER['HTTP_LOCK_TOKEN']) ||
       !preg_match( '@^\\s*<([^>]+)>\\s*$@',
                    $_SERVER['HTTP_LOCK_TOKEN'], $matches ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Missing required Lock-Token: header.'
    );
  $this->locktoken = $matches[1];
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  if (!DAV::$LOCKPROVIDER)
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);
  $lock = DAV::$LOCKPROVIDER->getlock(DAV::$PATH);
  if ( !$lock || $this->locktoken != $lock->locktoken )
    throw new DAV_Status(
      DAV::HTTP_CONFLICT,
      DAV::COND_LOCK_TOKEN_MATCHES_REQUEST_URI
    );
  DAV::$LOCKPROVIDER->unlock( $lock->lockroot );
  DAV::header(array(
    'status' => DAV::HTTP_NO_CONTENT
  ));
}
    
    
} // class DAV_Request_LOCK


