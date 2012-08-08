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
 * $Id: dav_status.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * A status, returned by the user.
 * @package DAV
 */
class DAV_Status extends Exception {

public $location = null;
public $conditions = array();

// The following two pseudo-constants are initialized at the bottom of this file:
public static $OK = null;
public static $NOT_FOUND = null;

/**
 * Constructor.
 * @param int $status the HTTP/1.1 status code, defaults to DAV::HTTP_INTERNAL_SERVER_ERROR
 * @param mixed $info One of the following:
 * - for a 3xx status: the URL of the Location: response header
 * - for a 4xx status: either
 *   - an array of pre- or postconditions, with the conditions as keys and extra XML as values
 *   - a free error string
 * - for a 5xx status: a free error string
 */
public function __construct(
  $status = DAV::HTTP_INTERNAL_SERVER_ERROR,
  $info = null
) {
  if ( $status < 300 ) {
    // Do nothing special.
  }
  elseif ( $status < 400 ) {
    $info = preg_split( '@\\s+@', "$info", 2 );
    if (!DAV::isValidURI($info[0]))
      throw new DAV_Status(
        DAV::HTTP_INTERNAL_SERVER_ERROR,
        "No location URI for status $status " . var_export($info, true)
      );
    $this->location = $info[0];
    $info = @$info[1];
  }
  elseif ( $status < 500 ) {
    if (is_array($info)) {
      foreach ($info as $condition => $xml)
        if ( !isset( DAV::$CONDITIONS[$condition] ) )
          throw new DAV_Status(
            DAV::HTTP_INTERNAL_SERVER_ERROR,
            "Invalid condition $condition with message " .
            var_export($message, true)
          );
      $this->conditions = $info;
      $info = null;
    } elseif ( isset( DAV::$CONDITIONS[$info]) ) {
      $this->conditions = array($info => null);
      $info = null;
    }
  }
  parent::__construct("$info", $status);
  if ($status >= 500)
    trigger_error("$this", E_USER_WARNING);
}


/**
 * Sends this status to client.
 * @return void
 */
public function output() {
  DAV::debug($this);
  $status = $this->getCode();
  if ($status < 300)
    throw new DAV_Status(
      DAV::HTTP_INTERNAL_SERVER_ERROR,
      "DAV_Status object with status $status " .
      var_export($this->getMessage(), true)
    );
    
  if ( DAV::HTTP_UNAUTHORIZED == $status &&
       DAV::$ACLPROVIDER &&
       DAV::$ACLPROVIDER->unauthorized() )
    return;
  elseif ( !empty($this->conditions) ) {
    $headers = array(
      'status' => $status,
      'Content-Type' => 'application/xml; charset="UTF-8"'
    );
    if ( $this->location )
      $headers['Location'] = $this->location;
    DAV::header($headers);
    echo DAV::xml_header() . '<D:error xmlns:D="DAV:">';
    foreach ($this->conditions as $condition => $xml) {
      echo "\n<D:" . $condition;
      echo $xml ? ">$xml</D:$condition>" : "/>";
    }
    echo "\n</D:error>";
  }
  elseif ( $this->location )
    DAV::redirect($status, $this->location);
  else {
    DAV::header(array(
      'status' => $status,
      'Content-Type' => 'text/plain; charset="UTF-8"'
    ));
    echo "HTTP/1.1 " . DAV::status_code($status) .
    	"\n" . $this->getMessage();
  }
}


} // class DAV_Status

DAV_Status::$OK = new DAV_Status(DAV::HTTP_OK);
DAV_Status::$NOT_FOUND = new DAV_Status(DAV::HTTP_NOT_FOUND);

