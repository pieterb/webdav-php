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
 * $Id: dav_namespaces.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */


/**
 * Function object for building namespace lists.
 * @package DAV
 */
class DAV_Namespaces {
  
  
const DAV = 'DAV:';
const XML = 'http://www.w3.org/XML/1998/namespace';

  
/**
 * @var array with url => prefix pairs.
 */
private $namespaces = array(
  self::DAV => 'D',
  self::XML => 'xml',
);


/**
 * @param $namespaceURI The URI of the namespace you want to get a prefix for.
 * @return string a prefix, including the trailing colon.
 */
public function prefix($namespaceURI) {
  if (empty($namespaceURI)) return '';
  if (!isset($this->namespaces[$namespaceURI]))
    $this->namespaces[$namespaceURI] =
      'ns' . ( count($this->namespaces) - 1 );
  return $this->namespaces[$namespaceURI] . ':';
}


/**
 * Returns the XML namespace (xmlns:) declarations that you can use within an
 * XML start tag.
 * @return string a bit of XML, to be used within an XML start tag.
 */
public function toXML() {
  $retval = '';
  $namespaces = $this->namespaces;
  unset($namespaces[self::DAV]);
  unset($namespaces[self::XML]);
  foreach ($namespaces as $ns => $prefix)
    $retval .= " xmlns:$prefix=\"$ns\"";
  return $retval;
}
  
  
} // class DAV_Namespaces
