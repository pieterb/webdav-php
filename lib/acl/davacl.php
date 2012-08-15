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

require_once 'dav.php';

/**
 * Just a namespace for constants and helper functions
 * @package DAVACL
 */
class DAVACL extends DAV {

// RFC3744 Principal properties:
const PROP_ALTERNATE_URI_SET = 'DAV: alternate-URI-set';
const PROP_PRINCIPAL_URL     = 'DAV: principal-URL';
const PROP_GROUP_MEMBER_SET  = 'DAV: group-member-set';
const PROP_GROUP_MEMBERSHIP  = 'DAV: group-membership';

public static $PRINCIPAL_PROPERTIES = array(
  self::PROP_ALTERNATE_URI_SET => 'alternate_URI_set',
  self::PROP_PRINCIPAL_URL     => 'principal_URL',
  self::PROP_GROUP_MEMBER_SET  => 'group_member_set',
  self::PROP_GROUP_MEMBERSHIP  => 'group_membership',
);


// RFC3253 REPORT related properties:
const PROP_SUPPORTED_REPORT_SET       = 'DAV: supported-report-set';

// RFC3744 Access control properties:
const PROP_OWNER                      = 'DAV: owner';
const PROP_GROUP                      = 'DAV: group';
const PROP_SUPPORTED_PRIVILEGE_SET    = 'DAV: supported-privilege-set';
const PROP_CURRENT_USER_PRIVILEGE_SET = 'DAV: current-user-privilege-set';
const PROP_ACL                        = 'DAV: acl';
const PROP_ACL_RESTRICTIONS           = 'DAV: acl-restrictions';
const PROP_INHERITED_ACL_SET          = 'DAV: inherited-acl-set';
const PROP_PRINCIPAL_COLLECTION_SET   = 'DAV: principal-collection-set';


// RFC5397 Access control property:
const PROP_CURRENT_USER_PRINCIPAL     = 'DAV: current-user-principal';

public static $ACL_PROPERTIES = array(
  self::PROP_OWNER                      => 'owner',
  self::PROP_GROUP                      => 'group',
  self::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
  self::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
  self::PROP_ACL                        => 'acl',
  self::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
  self::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
  self::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
  // RFC5397 Access control property:
  self::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
);




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


public static $PRINCIPAL_PROPERTIES = array(
  self::PROP_ALTERNATE_URI_SET => 'alternate_URI_set',
  self::PROP_PRINCIPAL_URL     => 'principal_URL',
  self::PROP_GROUP_MEMBER_SET  => 'group_member_set',
  self::PROP_GROUP_MEMBERSHIP  => 'group_membership',
);


public static $ACL_PROPERTIES = array(
  self::PROP_OWNER                      => 'owner',
  self::PROP_GROUP                      => 'group',
  self::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
  self::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
  self::PROP_ACL                        => 'acl',
  self::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
  self::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
  self::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
  // RFC5397 Access control property:
  self::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
);


// All pre- and postconditions that are defined in RFC3744:
const COND_ALLOWED_PRINCIPAL               = 'allowed-principal';
const COND_DENY_BEFORE_GRANT               = 'deny-before-grant';
const COND_GRANT_ONLY                      = 'grant-only';
const COND_LIMITED_NUMBER_OF_ACES          = 'limited-number-of-aces';
const COND_MISSING_REQUIRED_PRINCIPAL      = 'missing-required-principal';
const COND_NEED_PRIVILEGES                 = 'need-privileges';
const COND_NO_ABSTRACT                     = 'no-abstract';
const COND_NO_ACE_CONFLICT                 = 'no-ace-conflict';
const COND_NO_INHERITED_ACE_CONFLICT       = 'no-inherited-ace-conflict';
const COND_NO_INVERT                       = 'no-invert';
const COND_NO_PROTECTED_ACE_CONFLICT       = 'no-protected-ace-conflict';
const COND_NOT_SUPPORTED_PRIVILEGE         = 'not-supported-privilege';
const COND_NUMBER_OF_MATCHES_WITHIN_LIMITS = 'number-of-matches-within-limits';
const COND_RECOGNIZED_PRINCIPAL            = 'recognized-principal';


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


const REPORT_EXPAND_PROPERTY               = 'expand-property';
const REPORT_ACL_PRINCIPAL_PROP_SET        = 'acl-principal-prop-set';
const REPORT_PRINCIPAL_MATCH               = 'principal-match';
const REPORT_PRINCIPAL_PROPERTY_SEARCH     = 'principal-property-search';
const REPORT_PRINCIPAL_SEARCH_PROPERTY_SET = 'principal-search-property-set';


public static $REPORTS = array(
  self::REPORT_EXPAND_PROPERTY               => 'expand_property',
  self::REPORT_ACL_PRINCIPAL_PROP_SET        => 'acl_principal_prop_set',
  self::REPORT_PRINCIPAL_MATCH               => 'principal_match',
  self::REPORT_PRINCIPAL_PROPERTY_SEARCH     => 'principal_property_search',
  self::REPORT_PRINCIPAL_SEARCH_PROPERTY_SET => 'principal_search_property_set',
);


/**
 * @var DAVACL_ACL_Provider
 */
public static $ACLPROVIDER = null;


} // class DAVACL

DAV::$WEBDAV_PROPERTIES[DAVACL::PROP_SUPPORTED_REPORT_SET] = 'supported_report_set';

DAV::$SUPPORTED_PROPERTIES = array_merge(
  DAV::$SUPPORTED_PROPERTIES, array(
    // RFC3744 principal properties:
    DAVACL::PROP_ALTERNATE_URI_SET => 'alternate_URI_set',
    DAVACL::PROP_PRINCIPAL_URL     => 'principal_URL',
    DAVACL::PROP_GROUP_MEMBER_SET  => 'group_member_set',
    DAVACL::PROP_GROUP_MEMBERSHIP  => 'group_membership',
    // RFC3744 Access Control properties:
    DAVACL::PROP_OWNER                      => 'owner',
    DAVACL::PROP_GROUP                      => 'group',
    DAVACL::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
    DAVACL::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
    DAVACL::PROP_ACL                        => 'acl',
    DAVACL::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
    DAVACL::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
    DAVACL::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
    // RFC3253 REPORT related properties:
    DAVACL::PROP_SUPPORTED_REPORT_SET       => 'supported_report_set',
    // RFC5397 Access control property:
    DAVACL::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
  )
);

DAV::$PROTECTED_PROPERTIES = array_merge(
  DAV::$PROTECTED_PROPERTIES, array(
    // RFC3744 Principal properties
    DAVACL::PROP_ALTERNATE_URI_SET          => 'alternate_URI_set',
    DAVACL::PROP_PRINCIPAL_URL              => 'principal_URL',
    DAVACL::PROP_GROUP_MEMBERSHIP           => 'group_membership',
    // RFC3744 Access control properties
    DAVACL::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
    DAVACL::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
    DAVACL::PROP_ACL                        => 'acl',
    DAVACL::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
    DAVACL::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
    DAVACL::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
    // RFC3253 REPORT related properties:
    DAVACL::PROP_SUPPORTED_REPORT_SET       => 'supported_report_set',
    // RFC5397 Access control property:
    DAVACL::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
  )
);

DAV::$CONDITIONS = array_merge(
  DAV::$CONDITIONS, array(
    // RFC3744:
    DAVACL::COND_ALLOWED_PRINCIPAL                => DAVACL::COND_ALLOWED_PRINCIPAL,
    DAVACL::COND_DENY_BEFORE_GRANT                => DAVACL::COND_DENY_BEFORE_GRANT,
    DAVACL::COND_GRANT_ONLY                       => DAVACL::COND_GRANT_ONLY,
    DAVACL::COND_LIMITED_NUMBER_OF_ACES           => DAVACL::COND_LIMITED_NUMBER_OF_ACES,
    DAVACL::COND_MISSING_REQUIRED_PRINCIPAL       => DAVACL::COND_MISSING_REQUIRED_PRINCIPAL,
    DAVACL::COND_NEED_PRIVILEGES                  => DAVACL::COND_NEED_PRIVILEGES,
    DAVACL::COND_NO_ABSTRACT                      => DAVACL::COND_NO_ABSTRACT,
    DAVACL::COND_NO_ACE_CONFLICT                  => DAVACL::COND_NO_ACE_CONFLICT,
    DAVACL::COND_NO_INHERITED_ACE_CONFLICT        => DAVACL::COND_NO_INHERITED_ACE_CONFLICT,
    DAVACL::COND_NO_INVERT                        => DAVACL::COND_NO_INVERT,
    DAVACL::COND_NO_PROTECTED_ACE_CONFLICT        => DAVACL::COND_NO_PROTECTED_ACE_CONFLICT,
    DAVACL::COND_NOT_SUPPORTED_PRIVILEGE          => DAVACL::COND_NOT_SUPPORTED_PRIVILEGE,
    DAVACL::COND_NUMBER_OF_MATCHES_WITHIN_LIMITS  => DAVACL::COND_NUMBER_OF_MATCHES_WITHIN_LIMITS,
    DAVACL::COND_RECOGNIZED_PRINCIPAL             => DAVACL::COND_RECOGNIZED_PRINCIPAL,
  )
);

