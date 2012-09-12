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
 * @package DAV
 */
class DAV_Request_OPTIONS extends DAV_Request {


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource ) {
  header('DAV: 1, 3');
  header('DAV: access-control', false);
  header('DAV: <http://apache.org/dav/propset/fs/1>', false);
  $headers = array(
    'MS-Author-Via' => 'DAV',
    'Allow' => implode(', ', self::$ALLOWED_METHODS),
    'Content-Length' => 0
  );
  // For details on the following lines of code, see W3C's working draft on
  // "Cross Origin Resource Sharing" at <http://www.w3.org/TR/access-control/>
  $headers['Access-Control-Allow-Origin'] = (
      is_null( $_SERVER['HTTP_ORIGIN'] ) ||
      strtolower( $_SERVER['HTTP_ORIGIN'] ) == 'null'
    ) ? '*' : $_SERVER['HTTP_ORIGIN'];
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    $headers['Access-Control-Allow-Methods'] = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    $headers['Access-Control-Allow-Headers'] = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
  DAV::header( $headers );
}


} // class

