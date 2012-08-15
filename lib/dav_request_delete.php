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
 * Helper class for parsing LOCK request bodies.
 * @internal
 * @package DAV
 */
class DAV_Request_DELETE extends DAV_Request {private $p_depth = null;


public function depth() {
  if (is_null($this->p_depth)) {
    $this->p_depth = parent::depth();
    if ( is_null($this->p_depth) )
      $this->p_depth = DAV::DEPTH_INF;
  }
  $this->p_depth;
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource )
{
  $parent = $resource->collection();
  // The following statement excludes deletion of the root:
  if (!$parent)
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);

  if ( DAV::DEPTH_INF != $this->depth() )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Only Depth: infinity is allowed for DELETE requests.'
    );

  self::delete_member($parent, substr( $resource->path, strlen( $parent->path ) ) );

  if (DAV_Multistatus::active())
    DAV_Multistatus::inst()->close();
  else
    DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
}


/**
 * Recursive helper function.
 * Callers must check DAV_Multistatus::active() afterwards.
 * @see delete()
 * @param DAV_Collection $resource
 * @param string $member
 * @throws DAV_Status
 */
private static function delete_member( $resource, $member )
{
  $memberPath = $resource->path . $member;
  if ( '/' == substr($member, -1) ) {
    $failure = false;
    $memberResource = DAV::$REGISTRY->resource($memberPath);
    foreach ($memberResource as $child)
      try {
        self::delete_member($memberResource, $child);
      }
      catch (DAV_Status $e) {
        $failure = true;
        DAV_Multistatus::inst()->addStatus($memberResource->path . $child, $e);
      }
    if ($failure) return;
  }
  $resource->method_DELETE($member);
  DAV::$REGISTRY->forget($memberPath);
}


} // class

