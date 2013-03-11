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
 * $Id: dav_request_delete.php 3364 2011-08-04 14:11:03Z pieterb $
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
class DAV_Request_DELETE extends DAV_Request {


/**
 * Sets the default depth to 'infinity'.
 * @see DAV_Request::depth()
 */
public function depth() {
  $retval = parent::depth();
  return is_null($retval) ? DAV::DEPTH_INF : $retval;
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource )
{
  if ( DAV::DEPTH_INF !== $this->depth() )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Only Depth: infinity is allowed for DELETE requests.'
    );

  self::delete($resource);

  if (DAV_Multistatus::active())
    DAV_Multistatus::inst()->close();
  else
    DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
}


/**
 * Deletes $resource.
 * Callers must check DAV_Multistatus::active() afterwards.
 * @param DAV_Resource $resource
 * @throws DAV_Status
 */
public static function delete( $resource ) {
  $resource->assertLock();
  $resource->assertMemberLocks();
  $parent = $resource->collection();
  if (!$parent)
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);
  $parent->assertLock();
  $parent->assert(DAVACL::PRIV_UNBIND);
  self::delete_member( $parent, $resource );
}


/**
 * Recursive helper function.
 * Callers must check DAV_Multistatus::active() afterwards.
 * @see delete()
 * @param DAV_Collection $resource
 * @param string $memberPath a path
 * @throws DAV_Status
 */
private static function delete_member( $resource, $memberResource )
{
  if ( $memberResource instanceof DAV_Collection ) {
    $failure = false;
    $first = true;
    foreach ($memberResource as $child)
      try {
        $childResource = DAV::$REGISTRY->resource(
          $memberResource->path . $child
        );
        if (!$childResource)
          throw new DAV_Status(
            DAV::HTTP_INTERNAL_SERVER_ERROR,
            "Registry didn't generate resource for path " .
              $memberResource->path . $child
          );
        if ($first) {
          $memberResource->assert( DAVACL::PRIV_UNBIND );
          $first = false;
        }
        self::delete_member( $memberResource, $childResource );
      }
      catch (DAV_Status $e) {
        $failure = true;
        DAV_Multistatus::inst()->addStatus( $childResource->path, $e );
      }
    if ($failure) return;
  }
  $resource->method_DELETE(
    substr( $memberResource->path, strlen( $resource->path ) )
  );
  DAV::$REGISTRY->forget( $memberResource->path );
}


} // class

