<?php
/**
 * Contains tests for the DAV_Element_activelock class
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
 * Contains tests for the DAV_Element_activelock class
 * @package DAV
 * @subpackage tests
 */
class DAV_Element_activelockTest extends PHPUnit_Framework_TestCase {
  
  private $lock = null;

  
  protected function setUp() {
    $this->timeout = time() + 3600;
    $this->lock = new DAV_Element_activelock( array(
        'lockroot' => '/path',
        'depth' => DAV::DEPTH_INF,
        'locktoken' => 'qwerty',
        'owner' => '/path/to/user',
        'timeout' => $this->timeout
    ) );
    DAV::$SUBMITTEDTOKENS[ 'qwerty' ] = '';
  }

  public function testConstructor() {
    $this->assertSame( '/path'        , $this->lock->lockroot , 'DAV_Element_activelock::__constructor should set lockroot attribute' );
    $this->assertSame( DAV::DEPTH_INF , $this->lock->depth    , 'DAV_Element_activelock::__constructor should set depth attribute' );
    $this->assertSame( 'qwerty'       , $this->lock->locktoken, 'DAV_Element_activelock::__constructor should set locktoken attribute' );
    $this->assertSame( '/path/to/user', $this->lock->owner    , 'DAV_Element_activelock::__constructor should set owner attribute' );
    $this->assertSame( $this->timeout , $this->lock->timeout  , 'DAV_Element_activelock::__constructor should set timeout attribute' );
  }


  public function testToXML() {
    $this->lock->timeout = time() - 1; // Pretend the lock has timed out, because else we can't be sure what the timeout value in the XML will be
    $this->assertSame( "<D:activelock>\n<D:lockscope><D:exclusive/></D:lockscope>\n<D:locktype><D:write/></D:locktype>\n<D:depth>infinity</D:depth>\n<D:owner>/path/to/user</D:owner>\n<D:timeout>Second-0</D:timeout>\n<D:locktoken>\n<D:href>qwerty</D:href>\n</D:locktoken>\n<D:lockroot><D:href>/path</D:href></D:lockroot>\n</D:activelock>", $this->lock->toXML(), 'DAV_Element_activelock::toXML() should return the correct XML string' );
  }


  public function testToJSON() {
    $this->assertSame( json_encode( $this->lock ), $this->lock->toJSON(), 'DAV_Element_activelock::toJSON() should return the correct json value' );
  }


  public function testFromJSON() {
    $json = $this->lock->toJSON();
    $lock = DAV_Element_activelock::fromJSON( $json );    
    $this->assertSame( '/path'        , $lock->lockroot , 'DAV_Element_activelock::fromJSON() should create a new object with the same lockroot' );
    $this->assertSame( DAV::DEPTH_INF , $lock->depth    , 'DAV_Element_activelock::fromJSON() should create a new object with the same depth' );
    $this->assertSame( 'qwerty'       , $lock->locktoken, 'DAV_Element_activelock::fromJSON() should create a new object with the same locktoken' );
    $this->assertSame( '/path/to/user', $lock->owner    , 'DAV_Element_activelock::fromJSON() should create a new object with the same owner' );
    $this->assertSame( $this->timeout , $lock->timeout  , 'DAV_Element_activelock::fromJSON() should create a new object with the same timeout' );
    $this->lock->timeout = time() - 1; // Pretend the lock has not timed out
    $timedout_json = $this->lock->toJSON();
    $this->assertNull( DAV_Element_activelock::fromJSON( $timedout_json ), 'DAV_Element_activelock::fromJSON() should return null when the lock has timed out' );
  }

} // class DAV_Element_lockdiscoveryTest

// End of file