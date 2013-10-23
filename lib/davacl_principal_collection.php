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
 * $Id: davacl_principal_collection.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAVACL
 */

/**
 * Base class for all ACL-enabled resources.
 * @package DAVACL
 */
interface DAVACL_Principal_Collection {

/**
 * Searches for principals within this collection which match the criteria given in a REPORT principal-match request
 * 
 * @param  unknown  $input  Not known; Probably match something within DAV_Request_REPORT::handle_principal_match (which isn't implemented yet)
 */
public function report_principal_match ($input);


/**
 * Searches for principals within this collection which match the criteria given in a REPORT principal-property-search request
 * 
 * @param   array  $input  array of ( property => search string ) pairs.
 * @return  array          of principal urls.
 */
public function report_principal_property_search ($input);


/**
 * Searches for principals within this collection which match the criteria given in a REPORT principal-search-property-set request
 * @return  array  property => description pairs.
 */
public function report_principal_search_property_set();

}
