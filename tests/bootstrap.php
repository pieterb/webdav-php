<?php
/**
 * Sets up an environment to emulate a webserver environment
 * 
 * Copyright ©2013 SURFsara b.v., Amsterdam, The Netherlands
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
 * @package DAV
 * @subpackage tests
 */

// TODO: This is not the nice way. So I have to examine how I want to do bootstrapping for tests
$_SERVER = array();
$_SERVER['HTTPS'] = true;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_AUTH_USER'] = 'user';
$_SERVER['PHP_AUTH_PW'] = 'password';
$_SERVER['HTTP_REFERER'] = 'http://www.example.org/';
$_SERVER['SERVER_NAME'] = 'beehub.nl';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTP_USER_AGENT'] = 'MSIE';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['CONTENT_LENGTH'] = 100;
$_SERVER['HTTP_X_EXPECTED_ENTITY_LENGTH'] = '100';
$_SERVER['HTTP_DESTINATION'] = 'http://beehub.nl/destination';
$_SERVER['HTTP_OVERWRITE'] = 'F';
$_SERVER['HTTP_DEPTH'] = '0';
$_SERVER['HTTP_RANGE'] = '';
$_SERVER['HTTP_CONTENT_RANGE'] = '';
$_SERVER['HTTP_TIMEOUT'] = '';
$_SERVER['HTTP_IF'] = '';
$_SERVER['HTTP_IF_MATCH'] = '';
$_SERVER['HTTP_IF_UNMODIFIED_SINCE'] = '';
$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '';
$_SERVER['HTTP_IF_UNMODIFIED_SINCE'] = '';
$_SERVER['HTTP_IF_MATCH'] = '';
$_SERVER['HTTP_IF_NONE_MATCH'] = '';
$_SERVER['HTTP_CONTENT_LANGUAGE'] = 'en';
$_SERVER['HTTP_LOCK_TOKEN'] = '';
$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = '';
$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = '';
$_SERVER['HTTP_ORIGIN'] = '';
$_SERVER['SERVER_PROTOCOL'] = 'https';

require_once( dirname( dirname( __FILE__ ) ) . '/lib/bootstrap.php' );

// End of file
