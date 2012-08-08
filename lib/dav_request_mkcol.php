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
 * $Id: dav_request_mkcol.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class for parsing LOCK request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_MKCOL extends DAV_Request {


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource )
{
  
  if ($resource) {
    if ( $resource->isVisible() )
      throw new DAV_Status(DAV::HTTP_METHOD_NOT_ALLOWED);
    throw new DAV_Status(DAV::forbidden());
  }
  $resource = DAV::$REGISTRY->resource(dirname(DAV::$PATH));
  if ( !$resource or !$resource->isVisible() )
    throw new DAV_Status( DAV::HTTP_CONFLICT );
      
  $lockroot = DAV::assertLock($resource->path);
  if ($lockroot)
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot )
    );
  if ( ! $resource instanceof DAV_Collection )
    throw new DAV_Status(DAV::HTTP_METHOD_NOT_ALLOWED);
  if ( 0 < (int)@$_SERVER['CONTENT_LENGTH'] )
    throw new DAV_Status(DAV::HTTP_UNSUPPORTED_MEDIA_TYPE);
  $resource->method_MKCOL( basename(DAV::$PATH) );
  DAV::redirect(DAV::HTTP_CREATED, DAV::$PATH);
}


} // class

