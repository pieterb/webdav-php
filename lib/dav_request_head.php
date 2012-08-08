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
 * $Id: dav_request_head.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * HEAD.
 * @internal
 * @package DAV
 */
class DAV_Request_HEAD extends DAV_Request {


/**
 * Enter description here...
 *
 * @param string $path
 * @throws DAV_Status
 */
protected function __construct()
{
  parent::__construct();
}


/**
 * @param DAV_Resource $resource
 * @return array HTTP headers
 */
protected static function common($resource) {
  $headers = $resource->method_HEAD();
  if ( !isset($headers['Content-Length']) &&
       !is_null( $tmp = $resource->prop_getcontentlength() ) )
    $headers['Content-Length'] = htmlspecialchars_decode( $tmp );
  if ( !isset($headers['Content-Type']) )
    if ( !is_null( $tmp = $resource->prop_getcontenttype() ) )
      $headers['Content-Type'] = htmlspecialchars_decode( $tmp );
    else
      $headers['Content-Type'] = 'application/octet-stream';
  if ( !isset( $headers['ETag'] ) &&
       !is_null( $tmp = $resource->prop_getetag() ) )
    $headers['ETag'] = htmlspecialchars_decode( $tmp );
  if ( !isset( $headers['Last-Modified'] ) &&
       !is_null( $tmp = $resource->prop_getlastmodified() ) )
    $headers['Last-Modified'] = htmlspecialchars_decode( $tmp );
  if ( !isset( $headers['Content-Language'] ) &&
       !is_null( $tmp = $resource->prop_getcontentlanguage() ) )
    $headers['Content-Language'] = htmlspecialchars_decode( $tmp );
  $headers['Accept-Ranges'] = 'bytes';
  
  return $headers;
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource )
{
  $headers = self::common($resource);
  DAV::header($headers);
  return;
}


} // class

