<?php

/*·************************************************************************
 * Copyright ©2007-2012 Pieter van Beek, Almere, The Netherlands
 *           <http://purl.org/net/6086052759deb18f4c0c9fb2c3d3e83e>
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
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */


/**
 * Just a namespace for constants and helper functions
 * @package DAVACL
 */
class DAVLock {


// Various possible lock scopes. A lock can be EXCLUSIVE xor SHARED, not BOTH.
// But a resource may support neither, either, or both.
const LOCKSCOPE_NONE      = 0;
const LOCKSCOPE_EXCLUSIVE = 1;
const LOCKSCOPE_SHARED    = 2;
const LOCKSCOPE_BOTH      = 3;


const PROP_LOCKDISCOVERY      = 'DAV: lockdiscovery';
const PROP_SUPPORTEDLOCK      = 'DAV: supportedlock';

const COND_LOCK_TOKEN_MATCHES_REQUEST_URI   = 'lock-token-matches-request-uri';
const COND_LOCK_TOKEN_SUBMITTED             = 'lock-token-submitted';
const COND_NO_CONFLICTING_LOCK              = 'no-conflicting-lock';


/**
 * Defines if lock tokens are hidden in lockdiscovery properties.
 * @var boolean
 */
public static $HIDELOCKTOKENS = true;


/**
 * @var DAV_Lock_Provider
 */
public static $LOCKPROVIDER = null;


/**
 * An array of all statetokens submitted by the user in the If: header.
 * @var array <code>array( <stateToken> => <stateToken>, ... ></code>
 */
public static $SUBMITTEDTOKENS = array();


/**
 * @param string $path
 * @return mixed one of the following:
 * - DAV_Element_href of the lockroot of the missing token
 * - null if no lock was found.
 */
public static function assertLock($path) {
  if ( !self::$LOCKPROVIDER ) return null;
  if ( ( $lock = self::$LOCKPROVIDER->getlock($path) ) &&
       !isset( self::$SUBMITTEDTOKENS[$lock->locktoken] ) ) {
    $lockroot = DAV::$REGISTRY->resource($lock->lockroot);
    if (!$lockroot)
      throw new DAV_Status(DAV::HTTP_INTERNAL_SERVER_ERROR);
    return new DAV_Element_href(
      $lockroot->isVisible() ?
      $lock->lockroot : '/undisclosed-resource'
    );
  }
  return null;
}


/**
 * @param string $path
 * @return mixed one of the following:
 * - DAV_Element_href of the lockroot of the missing token
 * - null if no lock was found.
 */
public static function assertMemberLocks($path) {
  if ( !self::$LOCKPROVIDER ) return null;
  $locks = DAV::$LOCKPROVIDER->memberLocks( $path );
  foreach ($locks as $token => $lock)
    if ( !isset( self::$SUBMITTEDTOKENS[$token] ) )
      return new DAV_Element_href(
        DAV::$REGISTRY->resource($lock->lockroot)->isVisible() ?
        $lock->lockroot : '/undisclosed-resource'
      );
  return null;
}


} // class DAVLock


DAV::$WEBDAV_PROPERTIES = array_merge(
  DAV::$WEBDAV_PROPERTIES, array(
    DAVLock::PROP_LOCKDISCOVERY => 'lockdiscovery',
    DAVLock::PROP_SUPPORTEDLOCK => 'supportedlock',
  )
);

DAV::$SUPPORTED_PROPERTIES = array_merge(
  DAV::$SUPPORTED_PROPERTIES, array(
    DAVLock::PROP_LOCKDISCOVERY      => 'lockdiscovery',
    DAVLock::PROP_SUPPORTEDLOCK      => 'supportedlock',
  )
);


DAV::$PROTECTED_PROPERTIES = array_merge(
  DAV::$PROTECTED_PROPERTIES, array(
    DAVLock::PROP_LOCKDISCOVERY              => 'lockdiscovery',
    DAVLock::PROP_SUPPORTEDLOCK              => 'supportedlock',
  )
);


DAV::$CONDITIONS = array_merge(
  DAV::$CONDITIONS, array(
    DAVLock::COND_LOCK_TOKEN_MATCHES_REQUEST_URI   => DAVLock::COND_LOCK_TOKEN_MATCHES_REQUEST_URI,
    DAVLock::COND_LOCK_TOKEN_SUBMITTED             => DAVLock::COND_LOCK_TOKEN_SUBMITTED,
    DAVLock::COND_NO_CONFLICTING_LOCK              => DAVLock::COND_NO_CONFLICTING_LOCK,
  )
);

