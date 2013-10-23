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

/**
 * A mock for DAV_Multistatus
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAV_Test_Multistatus extends DAV_Multistatus {
  /**
   * This should be a identical copy of DAV_Multistatus::__construct()
   */
  private function __construct()
  {
    DAV::header( array(
      'Content-Type' => 'application/xml; charset="utf-8"',
      'status' => DAV::HTTP_MULTI_STATUS
    ) );
    echo DAV::xml_header() .
      '<D:multistatus xmlns:D="DAV:">';
  }
  /**
   * @var  DAV_Multistatus  The only instance of this class
   */
  protected static $inst = null;
  /**
   * Returns the only instance of this class
   * @return DAV_Multistatus
   */
  public static function inst() {
    if (null === self::$inst)
      self::$inst = new DAV_Test_Multistatus();
    return self::$inst;
  }
} // Class DAV_Test_Multistatus

// End of file