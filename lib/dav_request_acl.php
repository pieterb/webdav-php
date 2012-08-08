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
 * $Id: dav_request_acl.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class for parsing PROPPATCH request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_ACL extends DAV_Request {
    
  
/**
 * @var array of DAVACL_Element_ace objects
 */
public $aces = array();


/**
 * Enter description here...
 *
 * @param string $path
 */
protected function __construct() {
  parent::__construct();
//  if ( !isset($_SERVER['CONTENT_LENGTH']) ||
//       !$_SERVER['CONTENT_LENGTH'] )
//    throw new DAV_Status(
//      DAV::HTTP_UNPROCESSABLE_ENTITY,
//      'Couldn\'t find a proppatch request body.'
//    );
    
  // DEBUG
  $document = new DOMDocument();
  if ( ! $document->loadXML(
           $this->inputstring(),
           LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING
         ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST, 'Request body is not well-formed XML.'
    );
  
  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('D', 'DAV:');
  
  $nodelist = $xpath->query('/D:acl/D:ace');
  DAV::debug($nodelist);
  foreach( $nodelist as $ace ) {
    DAV::debug($ace);
    // Find the principal element:
    $principal = $xpath->query("D:principal/* | D:invert/D:principal/*", $ace);
    if (1 != $principal->length)
      throw new DAV_Status(
        DAV::HTTP_UNPROCESSABLE_ENTITY,
        $principal->length . ' principals in ACE');
    $principal = $principal->item(0);
    $p_invert = ('invert' == $principal->parentNode->parentNode->localName);
    
    $p = $principal->namespaceURI . ' ' . $principal->localName;
    if ('DAV: href' == $p)
      $p_principal = trim($principal->textContent);
    elseif (isset(DAVACL::$PRINCIPALS[$p]))
      $p_principal = $p;
    elseif ('DAV: property' == $p) {
      $e = $principal->firstChild;
      while ($e && XML_ELEMENT_NODE != $e->nodeType)
        $e = $e->nextSibling;
      if (!$e)
        throw new DAV_Status(
          DAV::HTTP_UNPROCESSABLE_ENTITY,
          "Missing property in ACE principal"
        );
      $p_principal = $e->namespaceURI . ' ' . $e->localName;
    } else
      throw new DAV_Status(
        DAV::HTTP_UNPROCESSABLE_ENTITY,
        "Don't understand principal element '$p'"
      );
    
    // Find the grant or deny part:
    $granted = $xpath->query('D:grant/D:privilege/*', $ace);
    $denied  = $xpath->query('D:deny/D:privilege/*',  $ace);
    if (  $granted->length &&  $denied->length or
         !$granted->length && !$denied->length )
      throw new DAV_Status(
        DAV::HTTP_UNPROCESSABLE_ENTITY,
        'Both grant and deny elements in ACE, or no privileges at all.'
      );
    if ($granted->length) {
      $privileges = $granted;
      $p_deny = false;
    } else {
      $privileges = $denied;
      $p_deny = true;
    }
    $p_privileges = array();
    foreach ($privileges as $p)
      $p_privileges[] = $p->namespaceURI . ' ' . $p->localName;
    
    // Finally, we look for the DAV:protected and DAV:inherited elements:
    $nodelist = $xpath->query('/D:ace/D:protected | /D:ace/D:inherited', $ace);
    if ($nodelist->length)
      throw new DAV_Status(
        DAV::HTTP_UNPROCESSABLE_ENTITY,
        'Cannot set protected or inherited ACEs'
      );
      
    $this->aces[] = new DAVACL_Element_ace(
      $p_principal, $p_invert, $p_privileges, $p_deny
    );
  }
    
  // DEBUG
  //DAV::debug($this->aces);
}


/**
 * @param DAVACL_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  if ($lockroot = DAV::assertLock(DAV::$PATH))
    throw new DAV_Status(
      DAV::HTTP_LOCKED, 
      array(DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot)
    );
  if ( ! $resource instanceof DAVACL_Resource )
    throw new DAV_Status(DAV::HTTP_METHOD_NOT_ALLOWED);
  $supported = $resource->user_prop_supported_privilege_set();
  $supported = DAVACL_Element_supported_privilege::flatten($supported);
  $restrictions = $resource->user_prop_acl_restrictions();
  foreach ($this->aces as $ace) {
    foreach ($ace->privileges as $privilege)
      // Check if the privilege is supported...
      if ( !isset( $supported[$privilege] ) )
        throw new DAV_Status(
          DAV::HTTP_FORBIDDEN, DAV::COND_NOT_SUPPORTED_PRIVILEGE
        );
      // ...and not abstract.
      elseif ( $supported[$privilege]['abstract'] )
        throw new DAV_Status(
          DAV::HTTP_FORBIDDEN, DAV::COND_NO_ABSTRACT
        );
    if ( $ace->principal instanceof DAV_Element_href ) {
      $path = $ace->principal->URIs[0];
      if ( !( $principal = DAV::$REGISTRY->resource( $path ) ) ||
           ! $principal instanceof DAVACL_Principal )
        throw new DAV_Status(
          DAV::HTTP_FORBIDDEN,
          DAV::COND_RECOGNIZED_PRINCIPAL
        );
    }
  }
  //TODO: enforce ACL restrictions
  $resource->method_ACL($this->aces);
}
  
  
} // class DAV_Request_PROPPATCH

