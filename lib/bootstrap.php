<?php
/**
 * Bootstraps the library
 *
 * This is still here to keep backward compatibility. If you load this library
 * using Composer, you can run \DAV::bootstrap() from your own bootstrapping
 * code instead.
 *
 * Copyright ©2014 SURFsara b.v., Amsterdam, The Netherlands
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
 * @package pieterb\dav
 */

namespace pieterb\dav;

/**
 * An autoloader for if this library is not used through Composer
 *
 * @param   string  $class  The class to load
 * @return  void
 */
function autoloader( $class ) {
  $elements = \explode( '\\', $class );
  $classLocalName = $elements[ \count( $elements) - 1 ];
  $localPath = __DIR__ . \DIRECTORY_SEPARATOR . $classLocalName . '.php';
  if ( is_readable( $localPath ) ) {
    require_once( $localPath );
  }
}
\spl_autoload_register( 'pieterb\dav\autoloader' );


// Then, let's call the \DAV::bootstrap() function to make sure this file is
// backwards compatible
\DAV::bootstrap();

// End of file