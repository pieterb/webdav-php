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


//=== COMMON STUFF =========================================================
private $json;
public function json() { return $this->json; }

/**
 * Constructor.
 * 
 * The caller is responsible for calling DAV_Multistatus::close() as well.
 */
private function __construct()
{
  $this->json = 'application/json' == $_SERVER['HTTP_ACCEPT'];
  DAV::header( array(
    'Content-Type' => (
      $this->json() ?
        'application/json' :
        'application/xml; charset="utf-8"'
    ),
    'status' => DAV::HTTP_MULTI_STATUS
  ) );
  // We need an if-then-else statement here to distinguish between content-types.
  echo $this->json() ? '[' : DAV::xml_header() . '<D:multistatus xmlns:D="DAV:">';
}


/**
 * The singleton instance.
 *
 * This is not a static function variable, because it needs to be accessed by
 * 2 methods: self::inst() and self::active().
 */
private static $inst = null;
/**
 * The singleton instance.
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


public function close() {
  static $closed = false;
  if ($closed) return;
  if ($this->currentStatus)
    $this->flushStatus();
  echo $this->json() ? ']' : "\n</D:multistatus>";
  $closed = true;
}


public function json_comma() {
  static $first = true;
  if ($first) {
    $first = false;
    return '';
  }
  return ',';
}


//=== STATUSES =============================================================


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
    $status = DAV_Status::get($status);
  if ( $status != $this->currentStatus ) {
    if ( null !== $this->currentStatus )
      $this->flushStatus();
    $this->currentStatus = $status;
  }
  $this->paths[] = $path;
  return $this;
}


private function flushStatus() {
  if ($this->json())
    $this->flushStatus_json();
  else
    $this->flushStatus_xml();

  $this->currentStatus = null;
  $this->paths = array();
}


private function flushStatus_json() {
  echo $this->json_comma();
  $json = array(
    'href' => $this->paths,
    'status' => $this->currentStatus->getCode()
  );

  if (!empty($this->currentStatus->conditions)) {
    $json['error'] = array();
    foreach ($this->currentStatus->conditions as $condition => $xml)
      $json['error'][$condition] = $xml;
  }

  $message = $this->currentStatus->getMessage();
  if ( !empty($message) )
    $json['responsedescription'] = $message;

  if (!empty($this->currentStatus->location))
    $json['location'] = $this->currentStatus->location;

  echo json_encode($json, JSON_FORCE_OBJECT);
}


private function flushStatus_xml() {
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
}


//=== PROPERTIES ===========================================================


/**
 * Add a response element to this MultiStatus response.
 * @param string $path
 * @param DAV_Element_response $response
 * @return DAV_Multistatus $this
 */
public function addResponse($response) {
  // Actually, this should never really happen. Maybe it's better to raise an
  // exception here?
  if ( null !== $this->currentStatus )
    $this->flushStatus();
  echo $this->json() ? $response->toJSON() : $response->toXML();
  //DAV::debug($response->toXML($path));
  //flush();
  return $this;
}


} // class DAV_Status

