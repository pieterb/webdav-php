<?php
/**
 * Contains the DAVACL_Test_Get_Resource class
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
 * A mock for DAVACL_Test_Resource
 * 
 * @internal
 * @package DAV
 * @subpackage tests
 */
class DAVACL_Test_Get_Resource extends DAVACL_Test_Resource {

  private $outputType = 'stream';


  public function setOutputType( $type ) {
    if ( in_array( $type, array( 'direct', 'string' ) ) ) {
      $this->outputType = $type;
    }else{
      $this->outputType = 'stream';
    }
  }


  public function method_GET() {
    $output = 'DAVACL_Test_Get_Resource::method_GET() called with output as ' . $this->outputType . ' for resource ' . $this->path . "\n";
    switch ( $this->outputType ) {
      case 'direct':
        print( $output );
        return;
      case 'string':
        return $output;
      default:
        $fp = fopen( 'php://temp/GET_body', 'r+' );
        fwrite( $fp, $output );
        rewind( $fp );
      return $fp;
    }
  }

} // Class DAVACL_Test_Get_Resource

// End of file