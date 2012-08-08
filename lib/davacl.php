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
 * $Id: davacl.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */


/**
 * Just a namespace for constants and helper functions
 * @package DAVACL
 */
class DAVACL {


const PRIV_ALL              = 'DAV: all';
const PRIV_BIND             = 'DAV: bind';
const PRIV_READ             = 'DAV: read';
const PRIV_READ_ACL         = 'DAV: read-acl';
const PRIV_READ_CURRENT_USER_PRIVILEGE_SET = 'DAV: read-current-user-privilege-set';
const PRIV_UNBIND           = 'DAV: unbind';
const PRIV_UNLOCK           = 'DAV: unlock';
const PRIV_WRITE            = 'DAV: write';
const PRIV_WRITE_ACL        = 'DAV: write-acl';
const PRIV_WRITE_CONTENT    = 'DAV: write-content';
const PRIV_WRITE_PROPERTIES = 'DAV: write-properties';

// <!ELEMENT principal (href | all | authenticated | unauthenticated
//   | property | self)>

const PRINCIPAL_ALL             = 'DAV: all';
const PRINCIPAL_AUTHENTICATED   = 'DAV: authenticated';
const PRINCIPAL_UNAUTHENTICATED = 'DAV: unauthenticated';
const PRINCIPAL_SELF            = 'DAV: self';

public static $PRINCIPALS = array(
  self::PRINCIPAL_ALL              => '<D:all/>',
  self::PRINCIPAL_AUTHENTICATED    => '<D:authenticated/>',
  self::PRINCIPAL_UNAUTHENTICATED  => '<D:unauthenticated/>',
  self::PRINCIPAL_SELF             => '<D:self/>',
);


const RESTRICTION_GRANT_ONLY         = 'DAV: grant-only';
const RESTRICTION_DENY_BEFORE_GRANT  = 'DAV: deny-before-grant';
const RESTRICTION_NO_INVERT          = 'DAV: no-invert';
const RESTRICTION_REQUIRED_PRINCIPAL = 'DAV: required-principal';
// <!ELEMENT required-principal
//   (all? | authenticated? | unauthenticated? | self? | href* |
//    property*)>


/**
 * @param string $hrefs
 * @return DAV_Element_href
 * @throws DAV_Status
 */
public static function parse_hrefs($hrefs) {
  $href = new DAV_Element_href();
  if (!preg_match('@^\\s*(?:<D:href(?:\\s+[^>]*)?>\\s*[^\\s<]+\\s*</D:href>\\s*)*$@', $hrefs))
    return $href;
  preg_match_all('@<D:href(?:\\s+[^>]*)?>\\s*([^\\s<]+)\\s*</D:href>@', $hrefs, $matches);
  foreach($matches[1] as $match)
    $href->addURI( DAV::parseURI( $match, false ) );
  return $href;
}


}
