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
 * $Id: dav_request_copy.php 3364 2011-08-04 14:11:03Z pieterb $
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
class DAV_Request_COPY extends DAV_Request {

  /**
   * Return the depth for the COPY request
   * 
   * If no Depth header is set, then DAV::DEPTH_INF will be returned
   *
   * @return  mixed  The depth
   */
public function depth() {
  $retval = parent::depth();
  return is_null($retval) ? DAV::DEPTH_INF : $retval;
}


/**
 * Determines whether the copy request is valid and if so, copies the resources
 * 
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  $destination = $this->destination();
  if ($resource instanceof DAV_Collection)
    $destination = DAV::slashify($destination);
  else
    // The next line is here to make the litmus test succeed. The author of
    // litmus had eir own doubts wether this is actually desirable behaviour,
    // but chose to require this behaviour anyway:
    $destination = DAV::unslashify($destination);

  // Can't move the root collection:
  if ( $this instanceof DAV_Request_MOVE &&
       '/' === DAV::getPath() )
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);

  // Assert proper Depth: header value:
  if ( DAV::DEPTH_1 === $this->depth() or
       $this instanceof DAV_Request_MOVE &&
       DAV::DEPTH_INF !== $this->depth() )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Illegal value for Depth: header.'
    );

  // Check: Can't move a collection to one of its members.
  if ( $this instanceof DAV_Request_MOVE &&
       '/' === substr(DAV::getPath(), -1) &&
       0 === strpos( $destination, DAV::getPath() ) )
    throw new DAV_Status(
      DAV::HTTP_FORBIDDEN,
      "Can't move a collection to itself or one of its members."
    );

  $resourceCollection = $resource->collection();
  if ( $this instanceof DAV_Request_MOVE ) {
    $resourceCollection->assertLock();
    $resource->assertLock();
    $resource->assertMemberLocks();
  }

  if ('/' !== $destination[0] ) {
    // Copy to an external URI?
    $isCreated = $resource->method_COPY_external( $destination, $this->overwrite() );
    if ( $this instanceof DAV_Request_MOVE &&
         !DAV_Multistatus::active() )
      DAV_Request_DELETE::delete($resource);
    if ( DAV_Multistatus::active())
      DAV_Multistatus::inst()->close();
    elseif ($isCreated)
      DAV::redirect(DAV::HTTP_CREATED, $destination);
    else
      DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
    return;
  }

  // Check: Won't move a resource to one of its parents.
  if ( 0 === strpos(
         DAV::slashify(DAV::getPath()),
         DAV::slashify($destination)
       ) )
    throw new DAV_Status(
      DAV::HTTP_NOT_IMPLEMENTED,
      "Won't move or copy a resource to one of its parents."
    );

  $destinationResource = DAV::$REGISTRY->resource( $destination );
  $destinationCollection = DAV::$REGISTRY->resource( dirname( $destination ) );
  if (!$destinationCollection)
    throw new DAV_Status(DAV::HTTP_CONFLICT, 'Unable to COPY to unexisting destination collection' );

  if ( $destinationResource ) {
    if ( ! $this->overwrite() ) {
      throw new DAV_Status(DAV::HTTP_PRECONDITION_FAILED);
    }else{
      $destinationResource->assertLock();
    }
  } else {
    $destinationCollection->assertLock();
  }

  if ($this instanceof DAV_Request_MOVE) {
    if ( DAV::$LOCKPROVIDER ) {
      foreach (DAV::$LOCKPROVIDER->memberLocks( DAV::getPath() ) as $lock)
        DAV::$LOCKPROVIDER->unlock( $lock->lockroot );
      if (( $lock = DAV::$LOCKPROVIDER->getlock( DAV::getPath() ) ))
        DAV::$LOCKPROVIDER->unlock( $lock->lockroot );
    }

    $resourceCollection->method_MOVE(
      basename($resource->path), $destination
    );
  }
  else {
    $this->copy_recursively( $resource, $destination );
  }

  #<<<<<<<<
  #// This version always returns a 207 Multistatus wrapper:
  #if (!DAV_Multistatus::active())
  #  if ( $destinationResource )
  #    DAV_Multistatus::inst()->addStatus(
  #      $resource->path,
  #      new DAV_Status( DAV::HTTP_NO_CONTENT )
  #    );
  #  else
  #    DAV_Multistatus::inst()->addStatus(
  #      $resource->path,
  #      new DAV_Status(
  #        DAV::HTTP_CREATED, DAV::path2uri($destination)
  #      )
  #    );
  #DAV_Multistatus::inst()->close();
  #========
  if (DAV_Multistatus::active())
    DAV_Multistatus::inst()->close();
  elseif ( $destinationResource )
    DAV::header( array( 'status' => DAV::HTTP_NO_CONTENT ) );
  else
    DAV::redirect(DAV::HTTP_CREATED, $destination);
  #>>>>>>>>
}


/**
 * Copy the resource and all its children
 * 
 * @param DAV_Collection $resource
 * @param string $destination
 * @param string $dr destinationRoot
 */
private function copy_recursively( $resource, $destination, $dr = null ) {
  if (!$dr) $dr = $destination;
  elseif ($dr === $resource->path) return;
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
