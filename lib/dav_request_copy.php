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
 * Helper class for handling COPY requests.
 * @internal
 * @package DAV
 */
class DAV_Request_COPY extends DAV_Request_DELETE {


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  $destination = $this->destination();
  if ($resource instanceof DAV_Collection)
    $destination = DAV::slashify($destination);
  // The next two lines are there to make the litmus test succeed. The author
  // of litmus had eir own doubts wether this is actually desirable behaviour,
  // but chose to require this behaviour anyway:
  else
    $destination = DAV::unslashify($destination);

  // Assert proper Depth: header value:
  if ( DAV::DEPTH_1 == $this->depth() or
       $this instanceof DAV_Request_MOVE &&
       DAV::DEPTH_INF != $this->depth() )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Illegal value for Depth: header.'
    );

  // Check: Can't move a collection to one of its members, which includes moving
  // the root collection.
  if ( $this instanceof DAV_Request_MOVE &&
       '/' == substr(DAV::$PATH, -1) &&
       0 === strpos( $destination, DAV::$PATH ) )
    throw new DAV_Status(
      DAV::HTTP_FORBIDDEN,
      "Can't move a collection to itself or one of its members."
    );

  // Copying to external destination:
  if ('/' !== $destination[0] ) {
    // True if the destination resource was newly created, otherwise false:
    $isCreated = $resource->method_COPY_external( $destination, $this->overwrite() );
    if ( DAV_Multistatus::active() ) {
      DAV_Multistatus::inst()->close();
      return;
    }
    if ( $this instanceof DAV_Request_MOVE )
      self::delete($resource);
    if ( DAV_Multistatus::active()) {
      DAV_Multistatus::inst()->addStatus(
        $destination, ( $isCreated ? DAV::HTTP_CREATED : DAV::HTTP_NO_CONTENT )
      );
      DAV_Multistatus::inst()->close();
      return;
    }
    if ($isCreated)
      DAV::redirect(DAV::HTTP_CREATED, $destination);
    else
      DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
    return;
  }

  // Check: Won't move a resource to one of its parents.
  if ( 0 === strpos(
         DAV::slashify(DAV::$PATH),
         DAV::slashify($destination)
       ) )
    throw new DAV_Status(
      DAV::HTTP_NOT_IMPLEMENTED, // It's not a 400 Bad Request!
      "Won't move or copy a resource to one of its parents."
    );

  // Let's see if there's already a resource at the destination URI:
  $destinationResource = DAV::$REGISTRY->resource( $destination );
  // If so, the behavior depends on the Overwrite: header.
  if ( $destinationResource ) {
    if ($this->overwrite()) {
      self::delete($destinationResource);
      if (DAV_Multistatus::active()) {
        DAV_Multistatus::inst()->addStatus(
          DAV::$PATH, DAV::HTTP_FORBIDDEN
        );
        DAV_Multistatus::inst()->close();
        return;
      }
    }
    else
      throw new DAV_Status(DAV::HTTP_PRECONDITION_FAILED);
  }
  elseif (!DAV::$REGISTRY->resource( dirname( $destination ) ) )
    throw new DAV_Status(DAV::HTTP_CONFLICT);

  if ($this instanceof DAV_Request_MOVE) {
    $resource->collection()->method_MOVE( basename($resource->path), $destination );
  }
  else {
    $this->copy_recursively( $resource, $destination );
  }

  if (DAV_Multistatus::active())
    DAV_Multistatus::inst()->close();
  elseif ( $destinationResource )
    DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
  else
    DAV::redirect(DAV::HTTP_CREATED, $destination);
}


/**
 * Deletes $resource.
 * 
 * Callers MUST check DAV_Multistatus::active() afterwards.
 * @param DAV_Resource $resource
 * @throws DAV_Status
 */
protected static function delete( $resource ) {
  $parent = $resource->collection();
  self::delete_member($parent, substr( $resource->path, strlen( $parent->path ) ) );
}


/**
 * @param DAV_Resource $resource
 * @param string $destination
 * @param string $dr destinationRoot
 */
private function copy_recursively( $resource, $destination, $dr = null ) {
  if (!$dr) $dr = $destination;
  elseif ($dr == $resource->path) return;
  $resource->method_COPY($destination);
  if ( ! $resource instanceof DAV_Collection ||
       DAV::DEPTH_INF !== $this->depth() )
    return;
  foreach( $resource as $member ) {
    $memberResource = DAV::$REGISTRY->resource( $resource->path . $member );
    try {
      $this->copy_recursively(
        $memberResource, $destination . $member, $dr
      );
    }
    catch(DAV_Status $e) {
      DAV_Multistatus::inst()->addStatus( $resource->path . $member, $e );
    }
  }
}


} // class
