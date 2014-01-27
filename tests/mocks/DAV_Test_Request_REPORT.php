<?php
/**
 * Contains the DAV_Test_Request_REPORT class
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

/**
 * A mock for DAV_Request_REPORT
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAV_Test_Request_REPORT extends DAV_Request_REPORT {

  /**
   * @return \DAV_Test_Request_REPORT
   */
  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }


  private static $inputstring = '';


  public static function setInputstring( $inputstring ) {
    self::$inputstring = $inputstring;
  }


  protected static function inputstring() {
    return self::$inputstring;
  }

} // Class DAV_Test_Request_REPORT

// End of file