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
 * $Id: dav_element_response.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * Helper class representing an array of WebDAV properties.
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
class DAV_Element_response {
  
  
/**
 * @var uri
 */
private $path;


/**
 * Array of properties.
 * @var array
 */
private $properties = array();


/**
 * @param string $path uri
 */
public function __construct($path) {
  $this->path = $path;
}
  
    
/**
 * Sets a property.
 * @param string $property MUST be "<namespaceURI> <localName>"
 * @param string $xmlvalue an XML fragment
 * @return DAV_Element_response $this
 */
public function setProperty( $property, $xmlvalue = null ) {
  $this->properties[ $property ] = $xmlvalue;
  return $this;
}


/*
 * Gets a property.
 * @param string $property MUST be "<namespaceURI> <localName>"
 * @return string an xml fragment
 */
//public function getProperty( $property ) {
//  return isset( $this->properties[ $property ] )
//    ? $this->properties[ $property ]
//    : null;
//}


/**
 * Array of statuses.
 * @var array
 */
private $status = array();


/**
 * Sets a status for a property.
 * @param string $property MUST be "<namespaceURI> <localName>"
 * @param DAV_Status $status
 * @return DAV_Element_response $this
 */
public function setStatus($property, $status) {
  $this->status[$property] = $status;
  return $this;
}



/**
 * Serializes this object to XML.
 * Must only be called by DAV_Multistatus.
 * @return string XML
 */
public function toXML() {
  // Set the default status to 200:
  foreach ( array_keys( $this->properties ) as $p )
    if ( !isset( $this->status[$p] ) )
      $this->status[$p] = DAV_Status::$OK;
      
  // Rearrange by status:
  $hashed_statusses = array();
  $hashed_properties = array();
  foreach ($this->status as $p => $s) {
    $hash = md5(
      $s->getCode() . "\t" . $s->getMessage() . "\t" .
      implode( "\t", $s->conditions ) . $s->location
    );
    $hashed_statusses[$hash] = $s;
    $hashed_properties[$hash][] = $p;
  }

  // Start generating some XML:
  $xml = "\n<D:response><D:href>$this->path</D:href>";
  // Each defined status gets its own <D:propstat> element:
  foreach ($hashed_statusses as $hash => $status) {
    $xml .= "\n<D:propstat><D:prop>";
    foreach ($hashed_properties[$hash] as $prop) {
      list($namespaceURI, $localName) = explode(' ', $prop);
      $xml .= "\n<";
      switch ($namespaceURI) {
			case 'DAV:': $xml .= "D:$localName"; break;
			case '':     $xml .= "$localName"; break;
			default:     $xml .= "ns:$localName xmlns:ns=\"$namespaceURI\"";
      }
      if (isset($this->properties[$prop])) {
        $xml .= '>' . $this->properties[$prop] . '</';
        switch ($namespaceURI) {
				case 'DAV:': $xml .= "D:"; break;
				case '':     break;
				default:     $xml .= "ns:";
        }
        $xml .= "$localName>";
      }
      else {
        $xml .= '/>';
      }
    }
    
    // And give the status itself!
    $xml .=
      "\n</D:prop>\n<D:status>HTTP/1.1 " .
      DAV::status_code($status->getCode()) .
      '</D:status>';
    if (!empty($status->conditions)) {
      $xml .= "\n<D:error>";
      foreach( $status->conditions as $condition )
        $xml .= @DAV::$CONDITIONS[$condition];
      $xml .= "</D:error>";
    }
    $message = $status->getMessage();
    if (!empty($message))
      $xml .= "\n<D:responsedescription>" .
        DAV::xmlescape($message) .
        '</D:responsedescription>';
    $xml .= "\n</D:propstat>";
  }
  $xml .= "\n</D:response>";
  return $xml;
}
  
    
} // class DAV_Element_response

