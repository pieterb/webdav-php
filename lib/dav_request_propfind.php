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
 * $Id: dav_request_propfind.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class for parsing PROPFIND request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_PROPFIND extends DAV_Request {

  
/**
 * One of 'allprop', 'propname', or 'prop'.
 * @var string
 */
public $requestType;


/**
 * @var array property names, eg. "DAV: getlastmodified"
 */
public $props = array();


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
  if (!strlen($input)) {
    $this->requestType = 'allprop';
    //DAV::debug('Empty PROPFIND body.');
    return;
  }
  
  $document = new DOMDocument();
  //DAV::debug( var_export( array( $_SERVER, DAV_Server::inst()->inputstring() ), true ) );
  if ( ! @$document->loadXML(
           $input,
           LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING
         ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Request body is not well-formed XML.'
    );
    
  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('D', 'DAV:');
  
  if ($xpath->evaluate('count(/D:propfind/D:propname)')) {
    $this->requestType = 'propname';
  }
  
  elseif ($xpath->evaluate('count(/D:propfind/D:prop)')) {
    $this->requestType = 'prop';
    $nodelist = $xpath->query('/D:propfind/D:prop/*');
    for ($i = 0; $i < $nodelist->length; $i++) {
      $element = $nodelist->item($i);
      // PHP5 DOM cannot destinguish between empty namespaces (forbidden) and
      // the default no-namespace. Therefor, this check has been commented out.
//      if ( empty($element->namespaceURI) &&
//          !$element->isDefaultNamespace($element-namespaceURI) )
//        throw new DAV_Status(
//          DAV::HTTP_BAD_REQUEST,
//          'Empty namespace URIs are not allowed.'
//        );
      $this->props[] = "{$element->namespaceURI} {$element->localName}";
    }
  }
    
  elseif ( $xpath->evaluate('count(/D:propfind/D:allprop)') ) {
    $this->requestType = 'allprop';
    $nodelist = $xpath->query('/D:propfind/D:include/*');
    for ($i = 0; $i < $nodelist->length; $i++) {
      $element = $nodelist->item($i);
      // PHP5 DOM cannot destinguish between empty namespaces (forbidden) and
      // the default no-namespace. Therefor, this check has been commented out.
//      if ( empty($element->namespaceURI) &&
//          !$element->isDefaultNamespace($element-namespaceURI) )
//        throw new DAV_Status(
//          DAV::HTTP_BAD_REQUEST,
//          'Empty namespace URIs are not allowed.'
//        );
            $this->props[] = "{$element->namespaceURI} {$element->localName}";
    }
  }
  
  else throw new DAV_Status(
    DAV::HTTP_UNPROCESSABLE_ENTITY,
    'No request type in XML request body.'
  );
  $this->props = array_unique($this->props);
}


public function depth() {
  $retval = parent::depth();
  return is_null($retval) ? DAV::DEPTH_INF : $retval;
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  /*
   * RFC4918 §9.1:
   * A client MUST submit a Depth header with a value of "0", "1", or
   * "infinity" with a PROPFIND request.  Servers MUST support "0" and "1"
   * depth requests on WebDAV-compliant resources and SHOULD support
   * "infinity" requests.  In practice, support for infinite-depth
   * requests MAY be disabled, due to the performance and security
   * concerns associated with this behavior.  Servers SHOULD treat a
   * request without a Depth header as if a "Depth: infinity" header was
   * included.
   *
   * RFC4918 §9.1.1:
   * 403 Forbidden - A server MAY reject PROPFIND requests on collections
   * with depth header of "Infinity", in which case it SHOULD use this
   * error with the precondition code 'propfind-finite-depth' inside the
   * error body.
   */
  if ( $resource instanceof DAV_Collection and
       DAV::DEPTH_INF == $this->depth() ) {
    //$d = debug_backtrace();
    //DAV::debug($d);
    throw new DAV_Status (
      DAV::HTTP_FORBIDDEN,
      DAV::COND_PROPFIND_FINITE_DEPTH
    );
  }
  
  $this->handle2( $resource );
  if ( $resource instanceof DAV_Collection &&
       DAV::DEPTH_1 == $this->depth() )
    foreach ($resource as $path) {
      $subpath = DAV::$PATH . $path;
      $subresource = DAV::$REGISTRY->resource( $subpath );
      if ($subresource->isVisible())
        $this->handle2( $subresource );
    }
    
  DAV_Multistatus::inst()->close();
}


/**
 * Recursively gathers properties in a WebDAV tree.
 * Called by {@link method_PROPFIND()}
 * @param DAV_Request_PROPFIND $propfind
 * @param DAV_Resource $resource
 * @return DAV_Props
 */
private function handle2( $resource ) {
  $props = $this->props;
  // A client may submit a 'propfind' XML element in the body of the
  // request method describing what information is being requested.  It is
  // possible to:
  switch($this->requestType) {

  // o Request property values for those properties defined in this
  //   specification (at a minimum) plus dead properties, by using the
  //   'allprop' element (the 'include' element can be used with
  //   'allprop' to instruct the server to also include additional live
  //   properties that may not have been returned otherwise),
  case 'allprop':
    foreach ( $resource->propname() as $key => $value )
      if ($value)
        $props[] = $key;
    $props = array_unique($props);

  // o Request particular property values, by naming the properties
  //   desired within the 'prop' element (the ordering of properties in
  //   here MAY be ignored by the server),
  case 'prop':
    $this->handle3( $resource, $props );
    break;

  // o Request a list of names of all the properties defined on the
  //   resource, by using the 'propname' element.
  case 'propname':
    $response = new DAV_Element_response($resource->path);
    $propname = $resource->propname();
    foreach ( array_keys( $propname ) as $key )
      $response->setStatus($key, DAV_Status::$OK);
    DAV_Multistatus::inst()->addResponse($response);
    break;
  }
}


/**
 * Called by {@link method_PROPFIND()}
 * @param string $path
 * @param DAV_Resource $resource
 * @param array $props
 */
private function handle3( $resource, $props ) {
  if ($resource instanceof DAVACL_Resource)
    $propprivs = $resource->property_priv_read($props);
  $response = new DAV_Element_response($resource->path);
  foreach ($props as $prop)
    if ( $resource instanceof DAVACL_Resource &&
         array_key_exists( $prop, $propprivs ) &&
         !$propprivs[$prop] )
      $response->setStatus($prop, new DAV_Status(DAV::forbidden()));
    else
      try {
        $value = $resource->prop($prop);
        if (!is_null($value))
          $response->setProperty($prop, $value);
        else
          $response->setStatus($prop, DAV_Status::$NOT_FOUND);
      }
      catch (DAV_Status $e) {
        $response->setStatus($prop, $e);
      }
  DAV_Multistatus::inst()->addResponse($response);
}


} // class DAV_Request_PROPFIND


