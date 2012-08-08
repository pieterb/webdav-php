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
 * $Id: dav_request_put.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * @package DAV
 */
class DAV_Request_PUT extends DAV_Request {


public $range_start = null;
public $range_end   = null;
public $range_total = null;


/**
 * @return array a struct with fields 'start', 'end' and 'total'.
 * 'start' and 'end' are always integers, 'total' is either an integer or null.
 */
private function init_range() {
  if (!isset($_SERVER['HTTP_CONTENT_RANGE'])) return;
  if ( !preg_match( '@^\\s*bytes\s*(\\d+)-(\\d+)/(\\*|\\d+)$@',
                    $_SERVER['HTTP_CONTENT_RANGE'], $matches ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      "Can't understand Content-Range: " . $_SERVER['HTTP_CONTENT_RANGE']
    );
  $this->range_start = (int)$matches[1];
  $this->range_end   = (int)$matches[2];
  $this->range_total = ('*' == $matches[3]) ? null : (int)$matches[3];
  if ( $this->range_start > $this->range_end or
       !is_null($this->range_total) &&
       $this->range_end >= $this->range_total )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      "Can't understand Content-Range: " . $_SERVER['HTTP_CONTENT_RANGE']
    );
}


/**
 * Enter description here...
 *
 * @param string $path
 * @throws DAV_Status
 */
protected function __construct()
{
  parent::__construct();
  $this->init_range();
}


/**
 * @param DAV_Resource $resource
 */
protected function handle( $resource ) {
  if (( $lockroot = DAV::assertLock( $resource ? DAV::$PATH : dirname( DAV::$PATH ) ) ))
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot )
    );
    
  $created = false;
  if ( !$resource ) {
    if (!is_null($this->range_start))
      throw new DAV_Status(DAV::HTTP_NOT_FOUND);
    $parent = DAV::$REGISTRY->resource(dirname(DAV::$PATH));
    if (!$parent || ! $parent instanceof DAV_Collection )
      throw new DAV_Status(DAV::HTTP_CONFLICT);
        
    $parent->create_member( basename( DAV::$PATH ) );
    $resource = DAV::$REGISTRY->resource(DAV::$PATH);
    $created = true;
  }
  elseif ( $resource instanceof DAV_Collection )
    throw new DAV_Status( DAV::HTTP_METHOD_NOT_ALLOWED, 'Method PUT not supported on collections.' );
    
  if (is_null($this->range_start)) {
    if ( isset($_SERVER['CONTENT_TYPE']) &&
         'application/octet-stream' != $_SERVER['CONTENT_TYPE'] )
      try { $resource->set_getcontenttype($_SERVER['CONTENT_TYPE']); }
      catch (DAV_Status $e) {}
    if ( isset($_SERVER['HTTP_CONTENT_LANGUAGE']) )
      try { $resource->set_getcontentlanguage($_SERVER['HTTP_CONTENT_LANGUAGE']); }
      catch (DAV_Status $e) {}
    $resource->storeProperties();
    $input = fopen('php://input', 'r');
    try {
      $resource->method_PUT($input);
      fclose($input);
    }
    catch(DAV_Status $e) {
      fclose($input);
      if ($created) $resource->method_DELETE();
      throw $e;
    }
  }
  else {
    $cl = $resource->user_prop_getcontentlength();
    if ( !is_null($cl) && (
           $this->range_start > $cl or
           !is_null($this->range_total) &&
           $this->range_total != $cl
         ) )
      throw new DAV_Status( DAV::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE );
    $input = fopen('php://input', 'r');
    try {
      $resource->method_PUT_range($input, $this->range_start, $this->range_end, $this->range_total);
      fclose($input);
    }
    catch(DAV_Status $e) {
      fclose($input);
      throw $e;
    }
  }
  
  if ($etag = $resource->prop_getetag())
    header('ETag: ' . htmlspecialchars_decode($etag));
  //DAV::debug($headers);
  if ($created)
    DAV::redirect(DAV::HTTP_CREATED, DAV::$PATH );
  else
    DAV::header(array('status' => DAV::HTTP_NO_CONTENT));
}


} // class

