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
 * A mock for DAV_Request_ACL
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAV_Test_Request_ACL extends DAV_Request_ACL {

  public static function inst() {
    $class = __CLASS__;
    return new $class();
  }


  protected static function inputstring() {
    return <<<EOS
<?xml version="1.0" encoding="utf-8" ?>
<acl xmlns="DAV:">
  <ace>
    <principal>
      <all />
    </principal>
    <grant>
      <privilege>
        <read/>
      </privilege>
    </grant>
  </ace>
  <ace>
    <principal>
      <href><![CDATA[/path/to/user]]></href>
    </principal>
    <grant>
      <privilege>
        <all/>
      </privilege>
    </grant>
  </ace>
</acl>
EOS
    ;
  }

} // Class DAV_Test_Request_ACL

// End of file