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
 * $Id: davacl_element_supported_privilege.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */


// <!ELEMENT supported-privilege
//  (privilege, abstract?, description, supported-privilege*)>
// <!ELEMENT privilege ANY>


/**
 * Helper class.
 * @package DAVACL
 */
class DAVACL_Element_supported_privilege {
  
  
private $privilege;


/**
 * @var bool
 */
private $abstract;


/**
 * @var string
 */
private $description;


/**
 * @var array of DAVACL_Element_supported_privilege objects
 */
private $supported_privileges = array();


/**
 * @param DAVACL_Element_supported_privilege $supported_privilege
 * @return DAVACL_Element_supported_privilege $this
 */
public function add_supported_privilege($supported_privilege) {
  $this->supported_privileges[] = $supported_privilege;
  return $this;
}


public function __construct($privilege, $abstract, $description) {
  $this->privilege = "$privilege";
  $this->abstract = (bool)$abstract;
  $this->description = "$description";
}


private function namespaces(&$namespaces) {
  $privilege = explode(' ', $this->privilege);
  $namespaces->prefix($privilege[0]);
  foreach($this->supported_privileges as $sp)
    $sp->namespaces($namespaces);
}


public function toXML($namespaces = false) {
  if (! $namespaces) {
    $namespaces = new DAV_Namespaces();
    $this->namespaces($namespaces);
    $t_namespaces = $namespaces->toXML();
  } else
    $t_namespaces = '';
  $t_privilege = explode(' ', $this->privilege);
  $t_privilege = '<' . $namespaces->prefix($t_privilege[0]) . $t_privilege[1] . '/>';
  $t_abstract = $this->abstract ? "\n<D:abstract/>" : '';
  $t_description = DAV::xmlescape($this->description);
  $t_supported_privileges = '';
  foreach ( $this->supported_privileges as $sp )
    $t_supported_privileges .= "\n" . $sp->toXML($namespaces);
  return <<<EOS
<D:supported-privilege$t_namespaces>
<D:privilege>$t_privilege</D:privilege>$t_abstract
<D:description>$t_description</D:description>$t_supported_privileges
</D:supported-privilege>
EOS;
}


/**
 * @param array $sps array of DAVACL_Element_supported_privilege
 * @return an array of privilege => object with members:
 * - 'children' => an array of self + all children, subchildren etc.
 * - 'abstract' => boolean
 */
public static function flatten($sps) {
  $retval = array();
  foreach ($sps as $sp) {
    $children = self::flatten($sp->supported_privileges);
    $retval = array_merge($retval, $children);
    
    $descendants = array( $sp->privilege );
    foreach ($children as $property => $child)
      $descendants = array_merge($descendants, $child['children']);
    $retval[$sp->privilege] = array(
      'children' => $descendants,
      'abstract' => $sp->abstract
    );
  }
  return $retval;
}


} // class DAV_Element_supported_privilege

