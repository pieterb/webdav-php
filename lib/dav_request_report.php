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
 * $Id: dav_request_report.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class for parsing REPORT request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_REPORT extends DAV_Request {

  
/**
 * One of 'allprop', 'propname', or 'prop'.
 * @var string
 */
public $type;


public $entity;


private static $SUPPORTED_REPORTS = array(
  'DAV: expand-property' => 'expand_property',
  'DAV: acl-principal-prop-set' => 'acl_principal_prop_set',
  'DAV: principal-match' => 'principal_match',
  'DAV: principal-property-search' => 'principal_property_search',
  'DAV: principal-search-property-set' => 'principal_search_property_set'
);


/**
 * Constructor.
 */
protected function __construct() {
  parent::__construct();
  
  /*
   * RFC4918 §9.1:
   * A client may choose not to submit a request body.  An empty PROPFIND
   * request body MUST be treated as if it were an 'allprop' request.
   */
  $input = $this->inputstring();
  if (!strlen($input))
    throw new DAV_Status( DAV::HTTP_BAD_REQUEST, 'Missing required request entity.' );
  
  $document = new DOMDocument();
  //DAV::debug( var_export( array( $_SERVER, DAV_Server::inst()->inputstring() ), true ) );
  if ( ! $document->loadXML(
           $input,
           LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING
         ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Request body is not well-formed XML.'
    );
    
  $documentElement = $document->documentElement;
  $reportType = $documentElement->namespaceURI . ' ' . $documentElement->localName;
  $this->type = @self::$SUPPORTED_REPORTS[$reportType];
  if ( !$this->type )
    throw new DAV_Status(
      DAV::HTTP_UNPROCESSABLE_ENTITY,
      'Unsupported REPORT type.'
    );
    
  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('D', 'DAV:');
  
  $parse = 'parse_' . $this->type;
  $this->$parse($document, $xpath);
}


/**
 * @param DOMDocument $document
 * @param DOMXPath $xpath
 */
private function parse_expand_property($document, $xpath) {
  $this->entity = $this->parse_expand_property_recursively($document->documentElement);
}


/**
 * @param DOMElement $element
 * @return array of ( property => array ) pairs, recursively.
 */
private function parse_expand_property_recursively($element) {
  $childNodes = $element->childNodes;
  $retval = array();
  for ($i = 0; $child = $childNodes->item($i); $i++)
    if ( XML_ELEMENT_NODE == $child->nodeType &&
         'property' == $child->localName &&
         'DAV:' == $child->namespaceURI ) {
      $namespaceURI = $child->attributes->getNamedItem('namespace');
      $namespaceURI = $namespaceURI ? $namespaceURI->value : 'DAV:';
      if ( !( $localName = $child->attributes->getNamedItem('name') ) )
        throw new DAV_Status(
          DAV::HTTP_UNPROCESSABLE_ENTITY,
          'Missing required "name" attribute in DAV:property element.'
        );
      $localName = $localName->value;
      $retval["$namespaceURI $localName"] = $this->parse_expand_property_recursively($child);
    }
  return $retval;
}


/**
 * @param DOMDocument $document
 * @param DOMXPath $xpath
 */
private function parse_acl_principal_prop_set($document, $xpath) {
  $this->entity = array();
  foreach ($xpath->query('/D:acl-principal-prop-set/D:prop/*') as $prop)
    $this->entity[] = $prop->namespaceURI . ' ' . $prop->localName;
}


/**
 * @param DOMDocument $document
 * @param DOMXPath $xpath
 */
private function parse_principal_match($document, $xpath) {
  // @TODO
}


/**
 * @param DOMDocument $document
 * @param DOMXPath $xpath
 */
private function parse_principal_property_search($document, $xpath) {
  $this->entity = array();
  foreach ($xpath->query('/D:principal-property-search/D:property-search') as $propertySearch) {
    $match = $xpath->query('D:match', $propertySearch)->item(0)->textContent;
    foreach ($xpath->query('D:prop/*', $propertySearch) as $prop)
        $this->entity['match'][$prop->namespaceURI . ' ' . $prop->localName][] = $match;
  }
  foreach ($xpath->query('/D:principal-property-search/D:prop/*') as $prop)
    $this->entity['prop'][] = $prop->namespaceURI . ' ' . $prop->localName;
}


/**
 * @param DOMDocument $document
 * @param DOMXPath $xpath
 */
private function parse_principal_search_property_set($document, $xpath) {
  // N/A: the element is always empty.
}


protected function handle( $resource ) {
  $handle = 'handle_' . $this->type;
  return $this->$handle($resource);
}


/**
 * @param DAV_Resource $resource
 */
private function handle_expand_property($resource) {
  DAV::debug($this->entity);
  $response = $this->handle_expand_property_recursively( DAV::$PATH, $this->entity );
  DAV_Multistatus::inst()->addResponse($response)->close();
}


/**
 * @param DAV_Resource $resource
 * @param array $properties
 * @return DAV_Element_response
 */
private function handle_expand_property_recursively($path, $properties) {
  if ( !( $resource = DAV::$REGISTRY->resource($path) ) )
    return null;
  $retval = new DAV_Element_response($path);
  foreach ($properties as $parent => $children) {
    try {
      $oldprop = $newprop = $resource->prop($parent);
      if ( $oldprop instanceof DAV_Element_href && $children ) {
        $newprop = '';
        foreach ($oldprop->URIs as $URI) {
          $tmp = $this->handle_expand_property_recursively( $URI, $children );
          $newprop .= $tmp ? $tmp->toXML() : "<D:href>{$URI}</D:href>";
        }
      }
      $retval->setProperty($parent, $newprop);
    }
    catch (DAV_Status $e) {
      $retval->setStatus($parent, $e);
    }
  }
  return $retval;
}


private function handle_acl_principal_prop_set($resource) {
  DAV::debug($this->entity);
  $resource->assert(DAVACL::PRIV_READ_ACL);
  $principals = array();
  foreach ($resource->user_prop_acl() as $ace) {
    if ('/' == $ace->principal[0] )
      $principals[$ace->principal] = true;
    elseif ( isset(DAVACL::$PRINCIPALS[$ace->principal] ) )
      continue;
    else {
      $href = $resource->prop($ace->principal);
      if ($href instanceof DAV_Element_href)
        $principals[$href->URIs[0]] = true;
    }
  }
  $multistatus = DAV_Multistatus::inst();
  foreach (array_keys($principals) as $href)
    if ($href && ($principal = DAV::$REGISTRY->resource( $href ) ) ) {
      $response = new DAV_Element_response($href);
      foreach ($this->entity as $property)
        try {
          $response->setProperty($property, $principal->prop($property));
        }
        catch(DAV_Status $e) {
          $response->setStatus($property, $e);
        }
      $multistatus->addResponse($response);
    }
}


private function handle_principal_match($resource) {
  // TODO
  throw new DAV_Status(DAV::HTTP_NOT_IMPLEMENTED);
}


private function handle_principal_property_search($principal_collection) {
  $principals = $principal_collection->report_principal_property_search($this->entity['match']);
  DAV_Multistatus::inst();
  foreach($principals as $path) {
    $principal = DAV::$REGISTRY->resource($path);
    if ( $principal && $principal->isVisible() ) {
      $response = new DAV_Element_response($path);
      foreach ($this->entity['prop'] as $prop) {
        try {
          $propval = $principal->prop($prop);
          $response->setProperty($prop, $propval);
        }
        catch (DAV_Status $e) {
          $response->setStatus($prop, $e);
        }
      }
      DAV_Multistatus::inst()->addResponse($response);
    }
  }
}


private function handle_principal_search_property_set($principal_collection) {
  $properties = $principal_collection->report_principal_search_property_set();
  echo DAV::xml_header();
  echo '<D:principal-search-property-set xmlns:D="DAV:">';
  foreach ($properties as $prop => $desc) {
    echo "\n<D:principal-search-property><D:prop>";
    list($namespaceURI, $localName) = explode(' ', $prop);
    echo "\n<";
    switch ($namespaceURI) {
		case 'DAV:': echo "D:$localName"; break;
		case '':     echo "$localName"; break;
		default:     echo "ns:$localName xmlns:ns=\"$namespaceURI\"";
    }
    echo '/>';
    if ($desc) echo
    	'<D:description xml:lang="en">' . DAV::xmlescape($desc) .
      '</D:description>';
    echo '</D:principal-search-property>';
  }
  echo "\n</D:principal-search-property-set>";
}


} // class DAV_Request_REPORT


