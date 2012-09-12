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
 * @package DAVACL
 */

/**
 * Set of DAV:href elements
 * @package DAVACL
 */
class DAVACL_Element_href implements JsonSerializable {


/**
 * Array of properties.
 * @var array
 */
public $URIs;


/**
 * Constructor
 * @param string $URI
 */
public function __construct($URIs = null) {
  if (is_array($URIs))
    $this->URIs = $URIs;
  elseif ($URIs instanceof DAVACL_Element_href)
    $this->URIs = $URIs->URIs;
  elseif ($URIs)
    $this->URIs = array("$URIs");
  else
    $this->URIs = array();
}


public function addURI($URI) {
  $this->URIs[] = $URI;
}


/**
 * An XML representation of the object.
 * @return string
 */
public function __toString() {
  return empty($this->URIs) ? '' :
  '<D:href>' . implode("</D:href>\n<D:href>", $this->URIs). '</D:href>';
}


/**
 * A JSON representation of the object.
 * @return string
 */
public function jsonSerialize($force_array = false) {
  return $this->URIs;
  // This is the old code
  if ($force_array || count($this->URIs) > 1)
    return $this->URIs;
  elseif (count($this->URIs) == 1)
    return $this->URIs[0];
  return null;
}


/**
 * @param string $hrefs
 * @return DAVACL_Element_href
 * @throws DAV_Status
 */
public static function parse($hrefs) {
  $href = new DAVACL_Element_href();
  if (!preg_match('@^\\s*(?:<D:href(?:\\s+[^>]*)?>\\s*[^\\s<]+\\s*</D:href>\\s*)*$@', $hrefs))
    return $href;
  preg_match_all('@<D:href(?:\\s+[^>]*)?>\\s*([^\\s<]+)\\s*</D:href>@', $hrefs, $matches);
  foreach($matches[1] as $match)
    $href->addURI( DAVACL::parseURI( $match, false ) );
  return $href;
}


} // class DAVACL_Element_href

