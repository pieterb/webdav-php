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
 * $Id: davacl_resource.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */

/**
 * Base class for all ACL-enabled resources.
 * @package DAVACL
 */
abstract class DAVACL_Resource extends DAV_Resource {

/**
 * @var  array  The cache with the effective ACL, if this is already determined
 */
private $eaclCache = null;


/**
 * Clears the effective ACL cache in case you need to make sure that the method effective_acl() does a new determination (instead of returning the cache)
 * 
 * @return  void
 */
public function clearEaclCache() {
  $this->eaclCache = null;
}

/**
 * Gets an ACL with the ACE's which are effective for the current user
 *
 * Called by self::assert() and self::prop_current_user_privilege_set()
 * @return array of arrays(bool $deny, array $privileges)
 */
public function effective_acl() {
  if (null !== $this->eaclCache)
    return $this->eaclCache;

  $this->eaclCache = array();

  // Get a list of principals:
  $principals = $this->current_user_principals();

  $aces = $this->user_prop_acl();
  foreach ($aces as $ace) {
    $match = false;
    switch($ace->principal) {
      case DAVACL::PRINCIPAL_ALL:
        $match = true;
        break;
      case DAVACL::PRINCIPAL_AUTHENTICATED:
        $match = $this->user_prop_current_user_principal();
        break;
      case DAVACL::PRINCIPAL_UNAUTHENTICATED:
        $match = !$this->user_prop_current_user_principal();
        break;
      case DAVACL::PRINCIPAL_SELF:
        $match = isset($principals[$this->path]);
        break;
      default:
        if ('/' === $ace->principal[0]) {
          $match = isset($principals[$ace->principal]);
        }
        elseif ( ( $p = $this->prop($ace->principal) ) instanceof DAV_Element_href )
          foreach ( $p->URIs as $URI )
            if ( isset($principals[$URI]) )
              $match = true;
    }
    if (!$match && !$ace->invert ||
         $match &&  $ace->invert) continue;
    $privs = array();
    $sps = $this->user_prop_supported_privilege_set();
    foreach( $ace->privileges as $privilegeName ) {
      $privilege = null;
      foreach( $sps as $supportedPrivilege ) {
        $privilege = $supportedPrivilege->findSubPrivilege( $privilegeName );
        if ( ! is_null( $privilege ) ) {
          break;
        }
      }
      $nonAggregatePrivileges = $privilege->getNonAggregatePrivileges();
      foreach ( $nonAggregatePrivileges as $nonAggregatePrivilege ) {
        $privs[] = $nonAggregatePrivilege->getNamespace() . ' ' . $nonAggregatePrivilege->getName();
      }
    }
    $this->eaclCache[] = array( $ace->deny, array_unique($privs));
  }
  return $this->eaclCache;
}


/**
 * @var  array  A cache of all already asserted privilege combinations
 */
private $assertCache = array();


/**
 * Clears the assert cache in case you need to make sure that the method assert() does a new determination (instead of returning the cache)
 * 
 * @return  void
 */
public function clearAssertCache() {
  $this->assertCache = array();
}


/**
 * Assert whether the current user has certain privileges for this resource
 *
 * @param array $privileges
 * @throws DAV_Status FORBIDDEN
 */
public function assert($privileges) {
  if (!is_array($privileges))
    $privileges = array((string)$privileges);

  $flags = array();
  foreach ( $privileges as $p ) {
    $supportedPrivileges = DAV::$ACLPROVIDER->user_prop_supported_privilege_set();
    $privilege = null;
    foreach ( $supportedPrivileges as $supportedPrivilege ) {
      $privilege = $supportedPrivilege->findSubPrivilege( $p );
      if ( ! is_null( $privilege ) ) {
        break;
      }
    }
    if ( is_null( $privilege ) ) {
      throw new DAV_Status( DAV::HTTP_INTERNAL_SERVER_ERROR, 'Privilege not found: ' . $p );
    }
    if ( $privilege->isAggregatePrivilege() ) {
      throw new DAV_Status( DAV::HTTP_INTERNAL_SERVER_ERROR, 'You cannot (and should not) assert aggregate privileges: ' . $p );
    }
    $flags[$p] = 0;
  }

  sort($privileges);
  $privstring = implode(',', $privileges);
  if (array_key_exists($privstring, $this->assertCache))
    if ($this->assertCache[$privstring])
      throw $this->assertCache[$privstring];
    else
      return true;

  $eacl = $this->effective_acl();
  foreach ($eacl as $ace) {
    list($deny, $privs) = $ace;
    foreach ($privs as $p)
      if ( isset($flags[$p]) && false !== $flags[$p] )
        $flags[$p] = !$deny;
    foreach ($flags as $f)
      if (true !== $f) continue 2;
    $this->assertCache[$privstring] = null;
    return true;
  }
  $need_privileges = '';
  foreach (array_keys($flags) as $priv)
    if (!$flags[$priv])
      $need_privileges .= '<' . DAV::expand($priv) . '/>';
  $this->assertCache[$privstring] = DAV::forbidden(
    array( DAV::COND_NEED_PRIVILEGES => $need_privileges )
  );
  throw $this->assertCache[$privstring];
}


/**
 * Overwrites DAV_Resource::propname()
 * 
 * @return array
 */
public function propname() {
  $retval = parent::propname();
  foreach ( array_keys( DAV::$ACL_PROPERTIES ) as $prop )
    if (!isset($retval[$prop]))
      $retval[$prop] = false;
  if ($this instanceof DAVACL_Principal)
    foreach ( array_keys(DAV::$PRINCIPAL_PROPERTIES) as $prop )
      if (!isset($retval[$prop]))
        $retval[$prop] = false;
  return $retval;
}


/**
 * Get a property in XML format
 * @see DAV_Resource::prop()
 * @param string $propname the name of the property to be returned,
 *        eg. "mynamespace: myprop"
 * @return string XML or NULL if the property is not defined.
 */
public function prop($propname) {
  if ( ( $method = @DAV::$ACL_PROPERTIES[$propname] ) or
       $this instanceof DAVACL_Principal &&
       ( $method = @DAV::$PRINCIPAL_PROPERTIES[$propname] ) )
    return call_user_func(array($this, "prop_$method"));
  return parent::prop($propname);
}


/**
 * Makes sure user_set_acl is called with the ACL provided
 * 
 * @param   array  $aces  The ACL in the form of an array of ACEs
 * @return  mixed         No idea what this method returns
 */
public function set_acl($aces) {
  return $this->user_set_acl($aces);
}


/**
 * Set the ACL for this resource
 * 
 * @param   array  $aces  The ACL in the form of an array of ACEs
 * @return  mixed         No idea what this method returns
 */
protected function user_set_acl($aces) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
 * Set or delete a property when a PROPPATCH request was made
 * 
 * @param string $propname the name of the property to be set.
 * @param string $value an XML fragment, or null to unset the property.
 * @see DAV_Resource::method_PROPPATCH()
 */
public function method_PROPPATCH($propname, $value = null) {
  if ( ( $method = @DAV::$ACL_PROPERTIES[$propname] ) or
       $this instanceof DAVACL_Principal &&
       ( $method = @DAV::$PRINCIPAL_PROPERTIES[$propname] ) )
    return call_user_func(array($this, "set_$method"), $value);
  return parent::method_PROPPATCH($propname, $value);
}


/**
 * Get the value of DAV: owner as a DAV_Element_href
 * 
 * @return DAV_Element_href
 */
final public function prop_owner() {
  $retval = $this->user_prop_owner();
  return $retval ? new DAV_Element_href($retval) : '';
}


/**
 * Return the value of the DAV: owner property as a string
 * 
 * @return string path
 */
public function user_prop_owner() {
  return null;
}


/**
 * Sets the DAV: owner property
 * 
 * @param   string      $owner  A piece of XML with exactly one <D:href> piece
 * @throws  DAV_Status          If there is not exactly 1 <D:href> piece
 */
final public function set_owner($owner) {
  $owner = DAVACL::parse_hrefs($owner);
  if (1 !== count($owner->URIs))
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Illegal value for property DAV:owner.'
    );
  $this->user_set_owner(DAV::parseURI($owner->URIs[0]));
}


/**
 * Sets the DAV: owner property
 * 
 * @param string $owner path
 */
protected function user_set_owner($owner) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
 * Returns the DAV: group property as a DAV_Element_href
 * 
 * @return DAV_Element_href
 */
final public function prop_group() {
  $retval = $this->user_prop_group();
  return $retval ? new DAV_Element_href($retval) : '';
}


/**
 * Returns the DAV: group property as a string
 * 
 * @return string path
 */
public function user_prop_group() { return null; }


/**
 * Sets the DAV: group property
 * 
 * @param   string      $group  The XML value of the group property
 * @throws  DAV_Status          With DAV::HTTP_BAD_REQUEST code when there are no groups specified in the XML
 */
final public function set_group($group) {
  $group = DAVACL::parse_hrefs($group);
  if (1 !== count($group->URIs))
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Illegal value for property DAV:group.'
    );
  $this->user_set_group(DAV::parseURI($group->URIs[0]));
}


/**
 * Sets the DAV: group property
 * 
 * @param string $group path
 */
protected function user_set_group($group) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
 * Return an XML part describing which privileges are supported
 * @return string XML
 */
final public function prop_supported_privilege_set() {
  $sps = $this->user_prop_supported_privilege_set();
  if (!$sps) return '';
  foreach ($sps as &$value)
    $value = $value->toXML();
  return implode("\n", $sps);
}


/**
 * Return all supported privileges
 * @return array of DAVACL_Element_supported_privilege objects.
 */
public function user_prop_supported_privilege_set() {
  return DAV::$ACLPROVIDER->user_prop_supported_privilege_set();
}


/**
 * Determine which privileges are (effectively) granted to the current user
 * 
 * @return  array  An array with all privileges which are granted
 */
public function user_prop_current_user_privilege_set() {
  $eacl = $this->effective_acl();
  $grant = $deny = array();
  foreach ($eacl as $acl) {
    foreach ($acl[1] as $priv)
      if ($acl[0] && !@$grant[$priv])
        $deny[$priv] = true;
      elseif (!$acl[0] && !@$deny[$priv])
        $grant[$priv] = true;
  }
  return array_keys($grant);
}


/**
 * Returns the current user privilege set as XML
 *
 * @return string XML
 */
final public function prop_current_user_privilege_set() {
  $retval = '';
  $cups = $this->user_prop_current_user_privilege_set();
  foreach ($cups as $cup) {
    $cup = explode(' ', $cup);
    $retval .= '<';
    if ( 'DAV:' === $cup[0] )
      $retval .= 'D:' . $cup[1] . '/>';
    else
      $retval .= $cup[1] . ' xmlns="' . $cup[0] . '"/>';
  }
  return $retval;
}


/**
 * Return the ACL in XML format
 *
 * @return string XML
 */
final public function prop_acl() {
  if ( !( $aces = $this->user_prop_acl() ) )
    return '';
  foreach ($aces as &$ace)
    $ace = $ace->toXML();
  return implode("\n", $aces);
}


/**
 * Returns the DAV: acl property as an array of ACEs
 * 
 * @return array an array of DAVACL_Element_ace objects
 */
abstract public function user_prop_acl();


/**
 * Returns the ACL restrictions in XML format
 *
 * @return string XML
 */
final public function prop_acl_restrictions() {
  $retval = '';
  foreach ($this->user_prop_acl_restrictions() as $restriction)
    if (is_array($restriction)) {
      // An array of required principals
      $retval .= "\n<D:required-principal>";
      foreach ($restriction as $principal)
        if ($p = @DAVACL::$PRINCIPALS[$principal])
          $retval .= "\n$p";
        elseif ('/' === $principal[0] )
          $retval .= "\n<D:href>" . $principal . '</D:href>';
        else
          $retval .= "\n<D:property><" . DAV::expand($principal) . '/></D:property>';
      $retval .= "\n</D:required-principal>";
    } else
      // Normal predefined restrictions:
      $retval .= '<' . DAV::expand($restriction) . '/>';
  return $retval;
}


/**
 * Returns the ACL restrictions
 * 
 * @return array of predefined restrictions and (optionally) an array of
 *   required principals.
 */
public function user_prop_acl_restrictions() {
  return DAV::$ACLPROVIDER->user_prop_acl_restrictions();
}


/**
 * Returns the DAV: inherited-acl-set property as a DAV_Element_href instance
 * 
 * @return DAV_Element_href
 */
final public function prop_inherited_acl_set() {
  $retval = $this->user_prop_inherited_acl_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Returns the DAV: inherited-acl-set property as an array of URIs
 * 
 * @return array an array of URIs
 */
public function user_prop_inherited_acl_set() { return null; }


/**
 * Returns the DAV: principal-collection-set property as a DAV_Element_href instance
 * 
 * @return DAV_Element_href
 */
final public function prop_principal_collection_set() {
  $retval = $this->user_prop_principal_collection_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Returns the DAV: principal-collection-set property as an array of URIs
 * 
 * @return array an array of paths
 */
public function user_prop_principal_collection_set() {
  return DAV::$ACLPROVIDER->user_prop_principal_collection_set();
}


/**
 * Return the alternate URI's as DAV_Element_href objects
 *
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_alternate_URI_set() {
  $retval = $this->user_prop_alternate_URI_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Returns the DAV: principal-url property as a DAV_Element_href instance
 * 
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_principal_URL() {
  $retval = $this->user_prop_principal_URL();
  return new DAV_Element_href( $retval ? $retval : $this->path );
}


/**
 * Returns the DAV: group-member-set property as a DAV_Element_href instance
 * 
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_group_member_set() {
  $retval = $this->user_prop_group_member_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Sets the DAV: group-member-set property
 * 
 * @param string $set an XML fragment
 * @see DAVACL_Principal
 */
final public function set_group_member_set($set) {
  $set = DAVACL::parse_hrefs($set)->URIs;
  foreach ( $set as &$uri )
    $uri = DAV::parseURI($uri, false);
  return $this->user_set_group_member_set($set);
}


/**
 * Sets the DAV: group-member-set property
 * 
 * @param array $set an array of paths
 * @see DAVACL_Principal
 * @internal must be public because of interface DAVACL_Principal.
 */
protected function user_set_group_member_set($set) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
 * Returns the DAV: group-membership property as a DAV_Element_href instance
 * 
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_group_membership() {
  $retval = $this->user_prop_group_membership();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Returns the current user principal as DAV_Element_href object
 *
 * @return DAV_Element_href
 */
final public function prop_current_user_principal() {
  $retval = $this->user_prop_current_user_principal();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * Returns the DAV: current-user-principal property as a string with the path
 * 
 * @return string path
 */
public function user_prop_current_user_principal() {
  return DAV::$ACLPROVIDER->user_prop_current_user_principal();
}


/**
 * Gets all principals that apply to this user, including recursive group memberships
 * 
 * @param   string  $path  The path to the current user's principal
 * @return  array          An array with all paths (both as key and as value) to principals that apply to this user
 */
final private static function current_user_principals_recursive($path) {
  $principal = DAV::$REGISTRY->resource($path);
  if ( ! ( $principal instanceof DAVACL_Principal ) ) {
    return array();
  }
  $retval = array($path => $path);
  foreach ( $principal->user_prop_group_membership() as $group )
    $retval = array_merge($retval, self::current_user_principals_recursive($group));
  return $retval;
}


/**
 * Get all principals that apply to the current user
 *
 * @return array of principals (either paths or properties),
 *         indexed by their own value.
 */
final public function current_user_principals() {
  $retval = array(DAVACL::PRINCIPAL_ALL => DAVACL::PRINCIPAL_ALL);
  if ( $current_user_principal = $this->user_prop_current_user_principal() ) {
    $retval = array_merge($retval, self::current_user_principals_recursive($current_user_principal));
    $retval[DAVACL::PRINCIPAL_AUTHENTICATED] = DAVACL::PRINCIPAL_AUTHENTICATED;
  }
  else {
    $retval[DAVACL::PRINCIPAL_UNAUTHENTICATED] = DAVACL::PRINCIPAL_UNAUTHENTICATED;
  }
  return $retval;
}


}
