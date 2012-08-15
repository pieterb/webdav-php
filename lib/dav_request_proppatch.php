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

  $input = $this->inputstring();
  if (!strlen($input)) {
    $this->requestType = 'allprop';
    //DAV::debug('Empty PROPFIND body.');
    return;
  }

  if ('application/json' == $SERVER['CONTENT_TYPE'])
    $this->initialize_json($input);
  else
    $this->initialize_xml($input);
}


/**
 * Called by __construct().
 * @param string $input some XML data.
 */
private function initialize_xml($input) {
  $document = new DOMDocument();
  if ( ! $document->loadXML(
           $input,
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
//        !$element->isDefaultNamespace($element->namespaceURI) )
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
}


/**
 * Called by __construct().
 * @param string $json some JSON data.
 */
private function initialize_json($input) {
  $this->props = json_decode($input, true);
  // Let's check the syntax of the passed data:
  if (!$this->props) throw DAV_Status::get(DAV::HTTP_BAD_REQUEST);
  foreach ($this->props as $key => $value) {
    // Each key must be a valid property name:
    DAV::parse_propname($key);
    // Check if the prop value is a valid XML fragment:
    $xml = DAV::xml_header() . '<document xmlns:D="DAV:">' . $value . '</document>';
    if ( !$document->loadXML(
           $xml, LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING
         ) )
      throw new DAV_Status( DAV::HTTP_BAD_REQUEST, $xml );
  }
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
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
  if (empty($errors)) {
    try { $resource->storeProperties(); }
    catch (DAV_Status $e) {
      foreach ( array_keys( $this->props ) as $propname )
        $errors[$propname] = $e;
    }
  }

  $response = new DAV_Element_response(DAV::$PATH);
  if (empty($errors))
    foreach ( array_keys( $this->props ) as $propname )
      $response->setStatus( $propname, DAV_Status::get(DAV::HTTP_OK) );
  else {
    foreach ( array_keys( $this->props ) as $propname )
      if ( !isset( $errors[$propname] ) )
        $errors[$propname] = DAV_Status::get(DAV::HTTP_FAILED_DEPENDENCY);
    foreach ($errors as $propname => $status)
      $response->setStatus($propname, $status);
  }
  DAV_Multistatus::inst()->addResponse($response);
  DAV_Multistatus::inst()->close();
}


} // class DAV_Request_PROPPATCH

