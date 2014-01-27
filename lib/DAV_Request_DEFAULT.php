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
 * $Id: dav_request_default.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * The default request handler
 * 
 * If this request handler is used, it basicly means that we didn't recognize
 * the request method (i.e. no GET, PUT, PROPFIND etc). So it should just
 * response with a HTTP 405 Method Not Allowed status.
 * 
 * @internal
 * @package DAV
 */
class DAV_Request_DEFAULT extends DAV_Request {

 
/**
 * Returns the HTTP 405 Method Not Allowed status code
 * 
 * This will only be called when an unknown/unsupported HTTP method is used. So
 * We'll return the correct status code and explain which methods are allowed.
 * 
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
public function handle($resource) {
  $allow = implode(', ', self::$ALLOWED_METHODS);
  DAV::header("Allow: $allow");
  $status = new DAV_Status(
    DAV::HTTP_METHOD_NOT_ALLOWED,
    "Allowed methods: $allow"
  );
  $status->output();
}
  

} // class

