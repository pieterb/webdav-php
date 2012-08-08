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
 * $Id: dav_multistatus.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */


/**
 * A status, returned by the user.
 * multistatus
 * |-response*
 * `-responsedescription? (not implemented)
 *   `-#PCDATA
 *   
 * response (status)
 * |-href+
 * | `-URL
 * |-status
 * | `-HTTP status code
 * |-error?
 * | `-<condition>+
 * |   `-ANY
 * |-responsedescription?
 * | `-#PCDATA
 * `-location?
 *   `-href
 *     `-URL
 * 
 * response (properties)
 * |-href
 * | `-URL
 * |-propstat+
 * | |-prop
 * | | `-<property>+
 * | |-status
 * | | `-HTTP status code
 * | |-error?
 * | | `-<condition>+
 * | |   `-ANY
 * | `-responsedescription?
 * |   `-#PCDATA
 * |-error? (not implemented)
 * | `-<condition>+
 * |   `-ANY
 * |-responsedescription? (not implemented)
 * | `-#PCDATA
 * `-location? (not implemented)
 *   `-href
 *     `-URL
 * @package DAV
 */
class DAV_Multistatus {

  
private $closed = false;
public function close() {
  if ($this->closed) return;
  if ($this->currentStatus)
    $this->flushStatus();
  echo "\n</D:multistatus>";
  //flush();
  $this->closed = true;
}


/**
 * @var DAV_Status
 */
private $currentStatus = null;
/**
 * An array of paths.
 * @var array
 */
private $paths = array();
  

/**
 * @param string $path
 * @param DAV_Status $status
 * @return DAV_Multistatus $this;
 */
public function addStatus($path, $status) {
  if ( ! $status instanceof DAV_Status )
    $status = new DAV_Status($status);
  if ( $status != $this->currentStatus ) {
    if ( null !== $this->currentStatus )
      $this->flushStatus();
    $this->currentStatus = $status;
  }
  $this->paths[] = $path;
  return $this;
}


/**
 * Add a response element to this MultiStatus response.
 * @param string $path
 * @param DAV_Element_response $response
 * @return DAV_Multistatus $this
 */
public function addResponse($response) {
  if ( null !== $this->currentStatus )
    $this->flushStatus();
  echo $response->toXML();
  //DAV::debug($response->toXML($path));
  //flush();
  return $this;
}


private function flushStatus() {
  echo "\n<D:response>";
  
  foreach ($this->paths as $path)
    echo "\n<D:href>{$path}</D:href>";
    
  $status = DAV::status_code($this->currentStatus->getCode());
  echo "\n<D:status>HTTP/1.1 $status</D:status>";
  
  if (!empty($this->currentStatus->conditions)) {
    echo '<D:error>';
    foreach ($this->currentStatus->conditions as $condition => $xml) {
      echo "\n<D:" . $condition;
      echo $xml ? ">$xml</D:$condition>" : "/>";
    }
    echo "\n</D:error>";
  }
  
  $message = $this->currentStatus->getMessage();
  if ( !empty($message) ) {
    $message = DAV::xmlescape($message);
    echo "\n<D:responsedescription>$message</D:responsedescription>";
  }
  
  if (!empty($this->currentStatus->location))
    echo "\n<D:location><D:href>" . $this->currentStatus->location .
      "</D:href></D:location>";
    
  echo "\n</D:response>";
  
  //flush();
  $this->currentStatus = null;
  $this->paths = array();
}
  
  
/**
 * Constructor.
 * The caller is responsible for calling DAV_Multistatus::close() as well.
 */
private function __construct()
{
  DAV::header( array(
    'Content-Type' => 'application/xml; charset="utf-8"',
    'status' => DAV::HTTP_MULTI_STATUS
  ) );
  echo DAV::xml_header() .
    '<D:multistatus xmlns:D="DAV:">';
}


private static $inst = null;
/**
 * @return DAV_Multistatus
 */
public static function inst() {
  if (null === self::$inst)
    self::$inst = new DAV_Multistatus();
  return self::$inst;
}


/**
 * @return bool true if a Multistatus body has been (partially) sent, otherwise
 *         false.
 */
public static function active() {
  return !is_null(self::$inst);
}
  
  
} // class DAV_Status

