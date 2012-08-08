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
 * $Id: dav_collection.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Abstract base class for all collections.
 * This base class implements the Iterator interface
 * @package DAV
 */
interface DAV_Collection extends Iterator {
  

const RESOURCETYPE = '<D:collection/>';


/**
 * Create a new, empty member.
 * @param string $name
 * @return DAV_Resource the new member.
 * @throws DAV_Status
 */
public function create_member( $name );


/**
 * Delete a member.
 * @param string $name
 * @throws DAV_Status From §9.6.1:
 * 424 (Failed Dependency) status codes SHOULD NOT be in the 207 (Multi-
 * Status) response for DELETE.  They can be safely left out because the
 * client will know that the ancestors of a resource could not be
 * deleted when the client receives an error for the ancestor's progeny.
 * Additionally, 204 (No Content) errors SHOULD NOT be returned in the
 * 207 (Multi-Status).  The reason for this prohibition is that 204 (No
 * Content) is the default success code.
 */
public function method_DELETE( $name );


/**
 * Handle the MOVE request.
 * This function should call DAV_Multistatus::inst()->addStatus() to report
 * errors. DAV_Status Sec.9.8.5 mentions the following status codes:
 * - 403 Forbidden
 * - 409 Conflict - A resource cannot be created at the destination until one or 
 *   more intermediate collections have been created. The server MUST NOT create 
 *   those intermediate collections automatically. Or, the server was unable to 
 *   preserve the behavior of the live properties and still move the resource to 
 *   the destination (see 'preserved-live-properties' postcondition).
 * - 412 A condition header failed. Specific to MOVE, this could mean that the 
 *   Overwrite header is "F" and the destination URL is already mapped to a 
 *   resource.
 * - 423 Locked - The source or the destination resource, the source or 
 * 	 destination resource parent, or some resource within the source or 
 *   destination collection, was locked. This response SHOULD contain the 
 *   'lock-token-submitted' precondition element.
 * - 507 Insufficient Storage
 * @param string $destination the destination path
 * @return void
 */
public function method_MOVE( $member, $destination );


/**
 * @param string $name
 */
public function method_MKCOL( $name );


/*
 * @see Iterator::current()
 * @return string the name of the current member.
 */
//public function current();
/*
 * @see Iterator::key()
 * @return scalar
 */
//public function key();
/*
 * @see Iterator::next()
 * @return void
 */
//public function next();
/*
 * @see Iterator::rewind()
 * @return void
 */
//public function rewind();
/*
 * @see Iterator::valid()
 * @return boolean
 */
//public function valid();


}
