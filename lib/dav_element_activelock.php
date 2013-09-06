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
 * $Id: dav_element_activelock.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Represents an active lock
 * @package DAV
 */
class DAV_Element_activelock {
// prop
// `-lockdiscovery
//   `-activelock*
//     |-lockroot
//     | `-href
//     |-lockscope
//     | `-exclusive|shared
//     |-locktype
//     | `-write
//     |-depth
//     |-locktoken?
//     | `-href
//     |-owner?
//     `-timeout? (Second-1*DIGIT|infinite)


/**
 * @var string a uri
 */
public $lockroot;


/**
 * @var string DAV::DEPTH_0 or DAV::DEPTH_INF
 */
public $depth;


/**
 * @var string the locktoken URI
 */
public $locktoken;


/**
 * @var string XML fragment
 */
public $owner;


/**
 * @var int timeout as absolute unixtime (not seconds remaining!) or 0
 */
public $timeout;


/**
 * Constructor
 * 
 * @param string $lockroot a path (not a URI!)
 * @param string $depth DAV::DEPTH_0 or DAV::DEPTH_INF
 * @param string $locktoken the locktoken URI
 * @param string $owner XML fragment
 * @param int $timeout as absolute unixtime (not seconds remaining!) or 0
 */
public function __construct($arg = array()) {
  $this->lockroot =  @$arg['lockroot'];
  $this->depth =     @$arg['depth'];
  $this->locktoken = @$arg['locktoken'];
  $this->owner =     @$arg['owner'];
  $this->timeout =   @$arg['timeout'];
}


/**
 * Serializes this lock to an XML string
 * @param array $tokens an array of tokens that may be displayed.
 * @return string an XML element
 */
public function toXML() {
  $t_lockroot = "\n<D:lockroot><D:href>{$this->lockroot}</D:href></D:lockroot>";
  if ( $this->timeout === 0 )
    $t_timeout = 'Infinite';
  else {
    $t_timeout = $this->timeout - time();
    $t_timeout = ( $t_timeout < 0 ) ? 'Second-0' : 'Second-' . $t_timeout;
  }
  $t_locktoken = isset(DAV::$SUBMITTEDTOKENS[$this->locktoken])
     ? "\n<D:locktoken>\n<D:href>{$this->locktoken}</D:href>\n</D:locktoken>"
     : '';
  $t_owner = empty($this->owner)
    ? ''
    : "\n<D:owner>{$this->owner}</D:owner>";
  return <<<EOS
<D:activelock>
<D:lockscope><D:exclusive/></D:lockscope>
<D:locktype><D:write/></D:locktype>
<D:depth>{$this->depth}</D:depth>{$t_owner}
<D:timeout>{$t_timeout}</D:timeout>{$t_locktoken}{$t_lockroot}
</D:activelock>
EOS;
}


/**
 * Serializes this lock to a JSON string
 * @return  string  The JSON string
 */
public function toJSON() {
  return json_encode($this);
}


/**
 * Creates a lock object from a JSON serialized lock
 * @param   string                     $json  A JSON serialized lock
 * @return  DAV_Element_activelock
 */
public static function fromJSON($json) {
  $value = json_decode($json, true);
  return ($value['timeout'] && $value['timeout'] < time()) ?
    null : new DAV_Element_activelock( $value );
}


} // class DAV_Element_activelock

// End of file