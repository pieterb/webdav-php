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
 * $Id: davacl_element_ace.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */

//<!ELEMENT acl (ace)* > 
//<!ELEMENT ace ((principal | invert), (grant|deny), protected?, 
// inherited?)> 
//
//<!ELEMENT principal (href) 
// | all | authenticated | unauthenticated 
// | property | self)> 
//
//<!ELEMENT all EMPTY> 
//<!ELEMENT authenticated EMPTY> 
//<!ELEMENT unauthenticated EMPTY> 
//<!ELEMENT property ANY> 
//<!ELEMENT self EMPTY> 
//
//<!ELEMENT invert principal> 
//
//<!ELEMENT grant (privilege+)> 
//<!ELEMENT deny (privilege+)> 
//<!ELEMENT privilege ANY> 
//
//<!ELEMENT protected EMPTY> 
//
//<!ELEMENT inherited (href)> 

/**
 * Set of DAV:href elements
 * @package DAVACL
 */
class DAVACL_Element_ace {
  
    
/**
 * @var string a path or property or predefined principal.
 */
public $principal;


/**
 * @var bool
 */
public $invert;


/**
 * @var bool true for a deny clause, false for a grant clause.
 */
public $deny;


/**
 * @var array
 */
public $privileges;


/**
 * @var bool
 */
public $protected;


/**
 * @var string optionally, a path.
 */
public $inherited;


/**
 * Constructor
 * @param string $URI
 */
public function __construct(
  $principal, $invert, $privileges, $deny,
  $protected = false, $inherited = null
) {
  $this->principal  = $principal;
  $this->invert     = (bool)$invert;
  $this->privileges = $privileges;
  $this->deny       = (bool)$deny;
  $this->protected  = (bool)$protected;
  $this->inherited  = $inherited ? (string)$inherited : null;
}


/**
 * An XML representation of the object.
 * @return string
 */
public function toXML() {
  $retval = "<D:ace>\n";
  // First the principal (or inversion thereof):
  if ('/' == $this->principal[0] )
    $principal = new DAV_Element_href( $this->principal );
  elseif ( !( $principal = @DAVACL::$PRINCIPALS[$this->principal] ) ) {
    $principal = explode(' ', $this->principal);
    $principal = "<D:property><{$principal[1]} xmlns=\"{$principal[0]}\"/></D:property>";
  }
  $principal = "<D:principal>{$principal}</D:principal>";
  if ($this->invert)
    $principal = "<D:invert>{$principal}</D:invert>";
  $retval .= $principal;
  // Second, the privileges (denied or granted):
  $privileges = '';
  foreach ($this->privileges as $privilege) {
    $privilege = explode(' ', $privilege);
    if ('DAV:' == $privilege[0])
      $privileges .= "<D:{$privilege[1]}/>";
    else
      $privileges .= "<{$privilege[1]} xmlns=\"{$privilege[0]}\"/>";
  }
  $retval .= $this->deny
    ? "\n<D:deny>{$privileges}</D:deny>"
    : "\n<D:grant>{$privileges}</D:grant>";
  // Finally, the DAV:protected and DAV:inherited props:
  if ($this->protected)
    $retval .= "\n<D:protected/>";
  if ($this->inherited)
    $retval .= "\n<D:inherited><D:href>{$this->inherited}</D:href></D:inherited>";
  $retval .= "\n</D:ace>";
  return $retval;
}


} // class DAV_Element_href

