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


private $eaclCache = null;
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
  $fsps = DAVACL_Element_supported_privilege::flatten(
    $this->user_prop_supported_privilege_set()
  );
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
        if ('/' === $ace->principal[0])
          $match = isset($principals[$ace->principal]);
        elseif ( ( $p = $this->prop($ace->principal) ) instanceof DAV_Element_href )
          foreach ( $p->URIs as $URI )
            if ( isset($principals[$URI]) )
              $match = true;
    }
    if (!$match && !$ace->invert ||
         $match &&  $ace->invert) continue;
    $privs = array();
    foreach ($ace->privileges as $p)
      $privs = array_merge($privs, $fsps[$p]['children']);
    $this->eaclCache[] = array( $ace->deny, array_unique($privs));
  }
  return $this->eaclCache;
}


private $assertCache = array();
/**
 * Assert whether the current user has certain privileges for this resource
 *
 * @param array $privileges
 * @throws DAV_Status FORBIDDEN
 */
public function assert($privileges) {
  if (!is_array($privileges))
    $privileges = array((string)$privileges);
  sort($privileges);

  $privstring = implode(',', $privileges);
  if (array_key_exists($privstring, $this->assertCache))
    if ($this->assertCache[$privstring])
      throw $this->assertCache[$privstring];
    else
      return true;

  $eacl = $this->effective_acl();
  $flags = array();
  foreach ($privileges as $p)
    $flags[$p] = 0;
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
 * By default, properties are writeble if the current user has PRIV_WRITE_PROPERTIES.
 * @param array $properties
 * @return array an array of (property => isWritable) pairs.
 */
public function property_priv_read($properties) {
  $retval = parent::property_priv_read($properties);
  if (isset($properties[DAV::PROP_ACL]))
    try {
      $this->assert(DAVACL::PRIV_READ_ACL);
    }
    catch( DAV_Status $e ) {
      $properties[DAV::PROP_ACL] = false;
    }
  if (isset($properties[DAV::PROP_CURRENT_USER_PRIVILEGE_SET]))
    try {
      $this->assert(DAVACL::PRIV_READ_CURRENT_USER_PRIVILEGE_SET);
    }
    catch( DAV_Status $e ) {
      $properties[DAV::PROP_CURRENT_USER_PRIVILEGE_SET] = false;
    }
  return $retval;
}


/**
 * By default, properties are writeble if the current user has PRIV_WRITE_PROPERTIES.
 * @param array $properties
 * @return array an array of (property => isWritable) pairs.
 */
public function property_priv_write($properties) {
  try {
    $this->assert(DAVACL::PRIV_WRITE_PROPERTIES);
    $allow = true;
  }
  catch( DAV_Status $e ) {
    $allow = false;
  }
  $retval = array();
  foreach ($properties as $prop) $retval[$prop] = $allow;
  return $retval;
}


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



public function set_acl($aces) {
  return $this->user_set_acl($aces);
}


protected function user_set_acl($aces) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
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
 * @see DAV_Resource::method_HEAD()
 */
public function method_HEAD() {
  $this->assert(DAVACL::PRIV_READ);
  return parent::method_HEAD();
}


/**
 * @return DAV_Element_href
 */
final public function prop_owner() {
  $retval = $this->user_prop_owner();
  return $retval ? new DAV_Element_href($retval) : '';
}


/**
 * @return string path
 */
public function user_prop_owner() {
  return null;
}


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
 * @param string $owner path
 */
protected function user_set_owner($owner) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
 * @return DAV_Element_href
 */
final public function prop_group() {
  $retval = $this->user_prop_group();
  return $retval ? new DAV_Element_href($retval) : '';
}


/**
 * @return string path
 */
public function user_prop_group() { return null; }


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
 * @param string $group path
 */
protected function user_set_group($group) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
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
 * @return array of DAVACL_Element_supported_privilege objects.
 */
public function user_prop_supported_privilege_set() {
  return DAV::$ACLPROVIDER->user_prop_supported_privilege_set();
}


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
        if ($p = DAVACL::$PRINCIPALS[$principal])
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
 * @return array of predefined restrictions and (optionally) an array of
 *   required principals.
 */
public function user_prop_acl_restrictions() {
  return DAV::$ACLPROVIDER->user_prop_acl_restrictions();
}


/**
 * @return DAV_Element_href
 */
final public function prop_inherited_acl_set() {
  $retval = $this->user_prop_inherited_acl_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
 * @return array an array of URIs
 */
public function user_prop_inherited_acl_set() { return null; }


/**
 * @return DAV_Element_href
 */
final public function prop_principal_collection_set() {
  $retval = $this->user_prop_principal_collection_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
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
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_principal_URL() {
  $retval = $this->user_prop_principal_URL();
  return new DAV_Element_href( $retval ? $retval : $this->path );
}


/**
 * @return DAV_Element_href
 * @see DAVACL_Principal
 */
final public function prop_group_member_set() {
  $retval = $this->user_prop_group_member_set();
  return $retval ? new DAV_Element_href( $retval ) : '';
}


/**
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
 * @param array $set an array of paths
 * @see DAVACL_Principal
 * @internal must be public because of interface DAVACL_Principal.
 */
protected function user_set_group_member_set($set) {
  throw new DAV_Status( DAV::HTTP_FORBIDDEN );
}


/**
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
 * @return string path
 */
public function user_prop_current_user_principal() {
  return DAV::$ACLPROVIDER->user_prop_current_user_principal();
}


final private static function current_user_principals_recursive($path) {
  $retval = array($path => $path);
  foreach (DAV::$REGISTRY->resource($path)->user_prop_group_membership() as $group)
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
