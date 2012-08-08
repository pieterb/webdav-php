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
 * $Id: dav_request_lock.php 3364 2011-08-04 14:11:03Z pieterb $
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
class DAV_Request_LOCK extends DAV_Request {
    
    
/**
 * @var string XML fragment
 */
public $owner = null;


/**
 * @var array an array of timeouts requested by the client, in order.
 */
public $timeout = array();


/**
 * @var bool indicates if the client requested a new lock, or a refresh.
 */
public $newlock = false;


private function init_timeout() {
  // Parse the Timeout: request header:
  if ( !isset( $_SERVER['HTTP_TIMEOUT'] ) ) return;
  $timeouts = preg_split( '/,\\s*/', $_SERVER['HTTP_TIMEOUT'] );
  foreach ($timeouts as $timeout) {
    if ( !preg_match( '@^\\s*(?:Second-(\\d+)|(Infinite))\\s*$@', $timeout, $matches ) )
      throw new DAV_Status(
        DAV::HTTP_BAD_REQUEST,
        "Couldn't parse HTTP header Timeout: " . $_SERVER['HTTP_TIMEOUT']
      );
    if ( (int)$matches[1] > 0 )
      $this->timeout[] = (int)$matches[1];
    elseif ( !empty( $matches[2] ) )
      $this->timeout[] = 0;
    else
      throw new DAV_Status(
        DAV::HTTP_BAD_REQUEST,
        "Couldn't parse HTTP header Timeout: " . $_SERVER['HTTP_TIMEOUT']
      );
  }
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
  $this->init_timeout();
  
  $input = $this->inputstring();
  if (empty($input)) return;

  // New lock!
  $this->newlock = true;
  
  $document = new DOMDocument();
  $result = $document->loadXML(
    $input,
    LIBXML_NOCDATA | LIBXML_NOENT | LIBXML_NSCLEAN | LIBXML_NOWARNING
  );
  
  if (!$result)
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST, 'Request body is not well-formed XML.'
    );
    
  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('D', 'DAV:');
  
  if ( $xpath->evaluate('count(/D:lockinfo/D:lockscope/D:shared)') == 1 )
    throw new DAV_Status(DAV::HTTP_BAD_REQUEST, 'Shared locks are not supported.');
  elseif ( $xpath->evaluate('count(/D:lockinfo/D:lockscope/D:exclusive)') != 1 )
    throw new DAV_Status(DAV::HTTP_BAD_REQUEST, 'No &lt;lockscope/&gt; element in LOCK request.');
    
  if ( $xpath->evaluate('count(/D:lockinfo/D:locktype/D:write)') != 1 )
    throw new DAV_Status(
      DAV::HTTP_UNPROCESSABLE_ENTITY, 'Unknown lock type in request body'
    );
    
  $ownerlist = $xpath->query('/D:lockinfo/D:owner');
  if ($ownerlist->length) {
    $ownerxml = '';
    $ownerchildnodes = $ownerlist->item(0)->childNodes;
    for ($i = 0; $child = $ownerchildnodes->item($i); $i++)
      $ownerxml .= DAV::recursiveSerialize($child);
    $this->owner = $ownerxml;
  }
  $this->newlock = true;
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
  if (!DAV::$LOCKPROVIDER)
    throw new DAV_Status(DAV::HTTP_FORBIDDEN);
  return $this->newlock ?
    $this->handleCreateLock($resource) :
    $this->handleRefreshLock($resource);
}


//private function respond($lock_token = null) {
//  $lock = DAV::$LOCKPROVIDER->getlock(DAV::$PATH);
//  $headers = array( 'Content-Type' => 'application/xml; charset="utf-8"' );
//  if ($lock_token) $headers['Lock-Token'] = "<{$token}>";
//  DAV::header($headers);
//  echo DAV::xml_header() . '<D:prop xmlns:D="DAV:"><D:lockdiscovery>' .
//    $lock->toXML() . '</D:lockdiscovery></D:prop>';
//}



/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
private function handleCreateLock($resource) {
  if ( ! $resource &&
       ( $lockroot = DAV::assertLock( dirname( DAV::$PATH ) ) ) )
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_LOCK_TOKEN_SUBMITTED => $lockroot )
    );
    
  // Check conflicting (parent) locks:
  if ( ( $lock = DAV::$LOCKPROVIDER->getlock( DAV::$PATH ) ) )
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      array( DAV::COND_NO_CONFLICTING_LOCK => new DAV_Element_href( $lock->lockroot ) )
    );
  if ( DAV::$LOCKPROVIDER->memberLocks( DAV::$PATH ) )
    throw new DAV_Status(
      DAV::HTTP_LOCKED,
      DAV::COND_NO_CONFLICTING_LOCK
    );
  // Find out the depth:
  $depth = $this->depth();
  if (DAV::DEPTH_1 == $depth)
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Depth: 1 is not supported for method LOCK.'
    );
    
  // Check unmapped collection resource:
  if ( !$resource && substr( DAV::$PATH, -1 ) === '/' )
    throw new DAV_Status(
      DAV::HTTP_NOT_FOUND,
      'Unmapped collection resource'
    );
    
  $headers = array( 'Content-Type' => 'application/xml; charset="utf-8"' );
  if ( !$resource ) {
    $parent = DAV::$REGISTRY->resource(dirname(DAV::$PATH));
    if (!$parent || !$parent->isVisible())
      throw new DAV_Status(DAV::HTTP_CONFLICT);
    $resource = $parent->create_member(basename(DAV::$PATH));
    // For M$, we need to mimic RFC2518:
    if ( false === strpos($_SERVER['HTTP_USER_AGENT'], 'Microsoft') ) {
      $headers['status'] = DAV::HTTP_CREATED;
      $headers['Location'] = DAV::$PATH;
    }
  }

  $token = DAV::$LOCKPROVIDER->setlock(
    DAV::$PATH, $depth, $this->owner, $this->timeout
  );
  DAV::$SUBMITTEDTOKENS[$token] = $token;
  $headers['Lock-Token'] = "<{$token}>";
  
  if ( !( $lockdiscovery = $resource->prop_lockdiscovery() ) )
    throw new DAV_Status( DAV::HTTP_INTERNAL_SERVER_ERROR );

  // Generate output:
  DAV::header($headers);
  echo DAV::xml_header() . '<D:prop xmlns:D="DAV:"><D:lockdiscovery>' .
    $lockdiscovery . '</D:lockdiscovery></D:prop>';
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Statuss
 */
private function handleRefreshLock($resource) {
  $if_header = $this->if_header;
  if ( !isset( $if_header[DAV::$PATH] ) ||
       !$if_header[DAV::$PATH]['lock'] )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      DAV::COND_LOCK_TOKEN_SUBMITTED
    );
  if ( !( $lock = DAV::$LOCKPROVIDER->getlock(DAV::$PATH) ) )
    throw new DAV_Status(
      DAV::HTTP_PRECONDITION_FAILED,
      array(DAV::COND_LOCK_TOKEN_MATCHES_REQUEST_URI)
    );
  DAV::$LOCKPROVIDER->refresh( $lock->lockroot, $lock->locktoken, $this->timeout );

  if ( !( $lockdiscovery = $resource->prop_lockdiscovery() ) )
    throw new DAV_Status( DAV::HTTP_INTERNAL_SERVER_ERROR );

  // Generate output:
  DAV::header('application/xml; charset="utf-8"');
  echo DAV::xml_header() . '<D:prop xmlns:D="DAV:"><D:lockdiscovery>' .
    $lockdiscovery . '</D:lockdiscovery></D:prop>';
}


} // class DAV_Request_LOCK


