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
  $destination = $this->destination();
  if ($resource instanceof DAV_Collection)
    $destination = DAV::slashify($destination);
  // The next two lines are there to make the litmus test succeed. The author
  // of litmus had eir own doubts wether this is actually desirable behaviour,
  // but chose to require this behaviour anyway:
  else
    $destination = DAV::unslashify($destination);
  
  // Assert locks:
  if (
       $this instanceof DAV_Request_MOVE && (
         ( $lockroot = DAV::assertLock( dirname( DAV::$PATH ) ) ) ||
         ( $lockroot = DAV::assertLock( DAV::$PATH ) ) ||
         ( $lockroot = DAV::assertMemberLocks( DAV::$PATH ) )
       ) ||
       ( $lockroot = DAV::assertLock( dirname( $destination ) ) ) ||
       $this->overwrite() && (
         ( $lockroot = DAV::assertLock( $destination ) ) ||
         ( $lockroot = DAV::assertMemberLocks( $destination ) )
       )
     )
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot )
    );
    
  // Assert proper Depth: header value:
  if ( DAV::DEPTH_1 == $this->depth() or
       $this instanceof DAV_Request_MOVE &&
       DAV::DEPTH_INF != $this->depth() )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Illegal value for Depth: header.'
    );

  if ( $this instanceof DAV_Request_MOVE &&
       '/' == DAV::$PATH )
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);

  // Check: Can't move a collection to one of its members.
  if ( $this instanceof DAV_Request_MOVE &&
       '/' == substr(DAV::$PATH, -1) &&
       0 === strpos( $destination, DAV::$PATH ) )
    throw new DAV_Status(
      DAV::HTTP_FORBIDDEN,
      "Can't move a collection to itself or one of its members."
    );

  if ('/' !== $destination[0] ) {
    $isCreated = $resource->method_COPY_external( $destination, $this->overwrite() );
    if ( $this instanceof DAV_Request_MOVE &&
         !DAV_Multistatus::active() )
      self::delete($resource);
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
         DAV::slashify(DAV::$PATH),
         DAV::slashify($destination)
       ) )
    throw new DAV_Status(
      DAV::HTTP_NOT_IMPLEMENTED,
      "Won't move or copy a resource to one of its parents."
    );

  $destinationResource = DAV::$REGISTRY->resource( $destination );
  if ( $destinationResource ) {
    if ($this->overwrite()) {
      self::delete($destinationResource);
      if (DAV_Multistatus::active()) {
        DAV_Multistatus::inst()->addStatus(
          DAV::$PATH, DAV::forbidden()
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
    if ( DAV::$LOCKPROVIDER ) {
      foreach (DAV::$LOCKPROVIDER->memberLocks( DAV::$PATH ) as $lock)
        DAV::$LOCKPROVIDER->unlock( $lock->lockroot );
      if (( $lock = DAV::$LOCKPROVIDER->getlock( DAV::$PATH ) ))
        DAV::$LOCKPROVIDER->unlock( $lock->lockroot );
    }
    if (!DAV_Multistatus::active())
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
  if ( '/' == substr($member, -1) ) { // member is a collection
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


/**
 * Deletes $resource.
 * Callers must check DAV_Multistatus::active() afterwards.
 * @param DAV_Resource $resource
 * @throws DAV_Status
 */
protected static function delete( $resource ) {
  $parent = $resource->collection();
  self::delete_member($parent, substr( $resource->path, strlen( $parent->path ) ) );
}


/**
 * @param DAV_Collection $resource
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
