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
 * @var  array( 'name' => '<xml_fragment>', ... )  If the property should be deleted, it will have NULL instead of <xml_fragment>
 */
public $props = array();


/**
 * Parse the request body
 */
protected function __construct() {
  parent::__construct();

  $input = $this->inputString();
  $document = new DOMDocument();
  if ( preg_match( '/xmlns:[a-zA-Z0-9]*=""/', $input ) ||
       ! @$document->loadXML(
           $input,
           LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING | LIBXML_NOERROR
         ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST, 'Request body is not well-formed XML.'
    );

  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('D', 'DAV:');

  $nodelist = $xpath->query('/D:propertyupdate/*/D:prop/*');
  for ($i = 0; $i < $nodelist->length; $i++) {
    $element = $nodelist->item($i);
    if ('DAV:' !== $element->parentNode->parentNode->namespaceURI)
      continue;
    if ('remove' === $element->parentNode->parentNode->localName)
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
//        $xml = '';
//    for ($j = 0; $child = $element->childNodes->item($j); $j++)
//      $xml .= DAV::recursiveSerialize($child);
//    $this->props["{$element->namespaceURI} {$element->localName}"] = null;
//  }
  // DEBUG
}


/**
 * Handle the PROPPATCH request
 *
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  $resource->assertLock();
  if (empty($this->props))
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'No properties found in request body.'
    );

  $priv_write = $resource->property_priv_write( array_keys( $this->props ) );

  $errors = array();
  foreach ($this->props as $name => $value) {
    try {
      if (@DAV::$PROTECTED_PROPERTIES[$name])
        throw new DAV_Status(
          DAV::HTTP_FORBIDDEN,
          DAV::COND_CANNOT_MODIFY_PROTECTED_PROPERTY
        );
      if ( !@$priv_write[$name] )
        throw DAV::forbidden();
      $resource->method_PROPPATCH($name, $value);
    }
    catch (DAV_Status $e) {
      $errors[$name] = $e;
    }
  }
  $response = new DAV_Element_response(DAV::getPath());
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

