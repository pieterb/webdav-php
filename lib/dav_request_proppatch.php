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
 * $Id: dav_request_proppatch.php 3349 2011-07-28 13:04:24Z pieterb $
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
class DAV_Request_PROPPATCH extends DAV_Request {
    
  
/**
 * @var array( array( 'type'  => 'set|remove',
 *                    'name'  => '<namespaceURI> <localName>',
 *                    'value' => '<xml_fragment>' ),
 *             ... )
 */
public $props = array();


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
//      DAV::HTTP_BAD_REQUEST,
//      'Couldn\'t find a proppatch request body.'
//    );
    
//  DAV::debug($this->inputstring());

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
  
  $nodelist = $xpath->query('/D:propertyupdate/*/D:prop/*');
  for ($i = 0; $i < $nodelist->length; $i++) {
    $element = $nodelist->item($i);
    // PHP5 DOM cannot destinguish between empty namespaces (forbidden) and
    // the default no-namespace. Therefor, this check has been commented out.
//    if ( empty($element->namespaceURI) &&
//        !$element->isDefaultNamespace($element-namespaceURI) )
//      throw new DAV_Status(
//        DAV::HTTP_BAD_REQUEST,
//        'Empty namespace URIs are not allowed.'
//      );
    if ('DAV:' != $element->parentNode->parentNode->namespaceURI)
      continue;
    if ('remove' == $element->parentNode->parentNode->localName)
      $this->props["{$element->namespaceURI} {$element->localName}"] = null;
    else {
      $xml = '';
      for ($j = 0; $child = $element->childNodes->item($j); $j++)
        $xml .= DAV::recursiveSerialize($child);
      $this->props["{$element->namespaceURI} {$element->localName}"] = $xml;
    }
  }
  
//  $nodelist = $xpath->query('/D:propertyupdate/D:remove/D:prop/*');
//  for ($i = 0; $i < $nodelist->length; $i++) {
//    $element = $nodelist->item($i);
//    // PHP5 DOM cannot destinguish between empty namespaces (forbidden) and
//    // the default no-namespace. Therefor, this check has been commented out.
////    if ( empty($element->namespaceURI) &&
////        !$element->isDefaultNamespace($element-namespaceURI) )
////      throw new DAV_Status(
////        DAV::HTTP_BAD_REQUEST,
////        'Empty namespace URIs are not allowed.'
////      );
//        $xml = '';
//    for ($j = 0; $child = $element->childNodes->item($j); $j++)
//      $xml .= DAV::recursiveSerialize($child);
//    $this->props["{$element->namespaceURI} {$element->localName}"] = null;
//  }
  // DEBUG
  //DAV::debug(var_export($this->props, true));
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  if (($lockroot = DAV::assertLock(DAV::$PATH) ))
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot )
    );
  //DAV::debug(DAV::$PATH);
  //DAV::debug($this->props);
  if (empty($this->props))
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'No properties found in request body.'
    );
    
  $errors = array();
  foreach ($this->props as $name => $value) {
    try {
      if (isset(DAV::$PROTECTED_PROPERTIES[$name]))
        throw new DAV_Status(
          DAV::HTTP_FORBIDDEN,
          DAV::COND_CANNOT_MODIFY_PROTECTED_PROPERTY
        );
      $resource->method_PROPPATCH($name, $value);
    }
    catch (DAV_Status $e) {
      $errors[$name] = $e;
    }
  }
  $response = new DAV_Element_response(DAV::$PATH);
  if (empty($errors)) {
    try { $resource->storeProperties(); }
    catch (DAV_Status $e) {
      foreach ( array_keys( $this->props ) as $propname )
        $errors[$propname] = $e;
    }
  }
  if (empty($errors))
    foreach ( array_keys( $this->props ) as $propname )
      $response->setStatus( $propname, DAV_Status::$OK );
  else {
    $failed_dependency = new DAV_Status(DAV::HTTP_FAILED_DEPENDENCY);
    foreach ( array_keys( $this->props ) as $propname )
      if ( !isset( $errors[$propname] ) )
        $errors[$propname] = $failed_dependency;
    foreach ($errors as $propname => $status)
      $response->setStatus($propname, $status);
  }
  DAV_Multistatus::inst()->addResponse($response);
  DAV_Multistatus::inst()->close();
}
  
  
} // class DAV_Request_PROPPATCH

