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
 * $Id: dav.php 3364 2011-08-04 14:11:03Z pieterb $
 **************************************************************************/

/**
 * Bootstraps the library
 * @package DAV
 */

// PHP messages destroy XML output -> switch them off.
ini_set('display_errors', 0);

// magic quotes spoil everything.
if ( ini_get('magic_quotes_gpc') ) {
  trigger_error('Please disable magic_quotes_gpc first.', E_USER_ERROR);
}

// We use autoloading of classes:
set_include_path( dirname(__FILE__) . PATH_SEPARATOR . get_include_path() );
spl_autoload_register( 'spl_autoload' );

// End of file