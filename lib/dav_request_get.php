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
 * $Id: dav_request_get.php 3349 2011-07-28 13:04:24Z pieterb $
 **************************************************************************/

/**
 * File documentation (who cares)
 * @package DAV
 */

/**
 * GET.
 * @internal
 * @package DAV
 */
class DAV_Request_GET extends DAV_Request_HEAD {


/**
 * @param int $entity_length
 * @return array of arrays with entries 'start' and 'end'
 */
public static function range_header( $entity_length ) {
  $retval = array();
  if ( !isset( $_SERVER['HTTP_RANGE'] ) )
    return $retval;
  $entity_length = (int)($entity_length);
  if ( !preg_match( '@^\\s*bytes\s*=\s*(.+)$@',
                    $_SERVER['HTTP_RANGE'],
                    $matches ) )
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      'Can\'t understand Range: ' . $_SERVER['HTTP_RANGE']
    );
  // ranges are comma separated
  foreach (explode(',', $matches[1]) as $range) {
    if ( !preg_match( '@^\\s*(\\d*)\\s*-\\s*(\\d*)\\s*$@',
                      $range, $matches ) )
      throw new DAV_Status(
        DAV::HTTP_BAD_REQUEST,
        'Can\'t understand Range: ' . $_SERVER['HTTP_RANGE']
      );
    $start = $matches[1];
    $end   = $matches[2];
    if ( '' == $start && '' == $end )
      throw new DAV_Status(
        DAV::HTTP_BAD_REQUEST,
        'Can\'t understand Range: ' . $_SERVER['HTTP_RANGE']
      );
    // RFC2616: 14.35.1
    // If the last-byte-pos value is absent, or if the value is greater than or
    // equal to the current length of the entity-body, last-byte-pos is taken to
    // be equal to one less than the current length of the entity- body in bytes. 
    if ( '' == $end ) {
      if (!$entity_length)
        throw new DAV_Status( DAV::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE );
      $end = $entity_length - 1;
    }
    else $end = (int)$end;
    if ( $end > $entity_length )
      $end = $entity_length - 1;
    if ( '' == $start ) {
      if ( $end > $entity_length )
        throw new DAV_Status( DAV::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE );
      $start = $entity_length - $end;
      $end = $entity_length - 1;
    }
    else $start = (int)$start;
    if ($end < $start)
      throw new DAV_Status(
        DAV::HTTP_BAD_REQUEST,
        'Can\'t understand Range: ' . $_SERVER['HTTP_RANGE']
      );
    // Multiple ranges shouldn't overlap:
    foreach ($retval as $value)
      if ( $start <= $value['end'] && $end >= $value['start'] )
        throw new DAV_Status(
          DAV::HTTP_BAD_REQUEST,
          'Can\'t understand Range: ' . $_SERVER['HTTP_RANGE']
        );
    $retval[] =
      array( 'start' => $start,
             'end'   => $end    );
  }
  return $retval;
}


/**
 * @param DAV_Resource $resource
 * @return void
 * @throws DAV_Status
 */
protected function handle( $resource )
{
  $headers = self::common($resource);
  
  $entity = $resource->method_GET();
  if (!is_resource($entity)) {
    if (is_null($entity)) {
      $headers['status'] = DAV::HTTP_NO_CONTENT;
      DAV::header($headers);
      return;
    }
    DAV::header($headers);
    echo "$entity";
    return;
  }
  // GET handler returned a stream
  
  // Try to find out the length of the total entity:
  if ( isset($headers['Content-Length']) ) {
    $entity_length = (int)($headers['Content-Length']);
  }
  else {
    $stat = @fstat($entity);
    $entity_length = ( !is_array( $stat ) || !isset( $stat['size'] ) ) ?
      '*' : $stat['size'];
  }
  
  // process Range: header if present
  $ranges = self::range_header( $entity_length );
  
  if ( ( isset($headers['status']) &&
         substr($header['status'], 0, 3) != '200' ) ||
       empty( $ranges ) ) {
    // No byte ranges, or unexpected status code.
    // We just relay everything as-is.
    DAV::header($headers);
    while (!feof($entity))
      echo fread($entity, DAV::$CHUNK_SIZE);
    fclose($entity);
    return;
  }
  
  //echo 'debugdebug'; exit;
  // One or more Ranges!
  $headers['status'] = DAV::HTTP_PARTIAL_CONTENT;
  if (1 == count($ranges)) {
    $range = $ranges[0];
    $content_length = $range['end'] - $range['start'] + 1;
    $headers['Content-Length'] = $content_length;
    $headers['Content-Range'] =
      "bytes {$range['start']}-{$range['end']}/$entity_length";
    DAV::header($headers);
    if ( 0 != fseek ($entity, $range['start'], SEEK_SET) ) {
      // The stream is not seekable
      $size = $range['start'];
      while ($size && !feof($entity)) {
        $buffer = fread(
          $entity,
          ($size < DAV::$CHUNK_SIZE) ? $size : DAV::$CHUNK_SIZE
        );
        $size -= strlen($buffer);
      }
    }
    if ( $entity_length === $range['end'] + 1 )
      fpassthru($entity);
    else {
      $size = $content_length;
      while ($size && !feof($entity)) {
        $buffer = fread(
          $entity,
          ($size < DAV::$CHUNK_SIZE) ? $size : DAV::$CHUNK_SIZE
        );
        $size -= strlen($buffer);
        echo $buffer;
      }
      if ($size)
        trigger_error(
          var_export( debug_backtrace(), true ),
          E_USER_WARNING
        );
    }
    fclose($entity);
    return;
  }
  
  // Multiple ranges!
  $multipart_separator = 'SDisk_' . strtr( microtime(), '. ', '__');
  // Remove all Content-* headers from the HTTP response headers.
  // They are moved to the body parts.
  $partheaders = array();
  foreach (array_keys($headers) as $header)
    if ( substr( strtolower($header), 0, 8 ) == 'content-') {
      $partheaders[$header] = $headers[$header];
      unset ($headers[$header]);
    }

  $headers['Content-Type'] = "multipart/byteranges; boundary={$multipart_separator}";
  DAV::header($headers);
  echo "This is a message in multipart MIME format.\r\n";
  $current_position = 0;
  foreach ($ranges as $range) {
    if (0 == fseek ($entity, $range['start'], SEEK_SET))
      $current_position = $range['start'];
    elseif ($range['start'] >= $current_position) {
      $skip = $range['start'] - $current_position;
      while ($skip && !feof($entity)) {
        $buffer = fread(
          $entity,
          ($skip < DAV::$CHUNK_SIZE) ? $skip : DAV::$CHUNK_SIZE
        );
        $skip -= strlen($buffer);
        echo $buffer;
      }
      $current_position = $range['start'] - $skip;
      if ($skip) {
        $current_position = $range['start'] - $skip;
        trigger_error(
          var_export( debug_backtrace(), true ),
          E_USER_WARNING
        );
        continue;
      }
    }
    else {
      trigger_error(
        var_export(debug_backtrace(), true),
        E_USER_WARNING
      );
      continue;
    }
    
    echo "\r\n--{$multipart_separator}\r\n";
    $partheaders['Content-Range'] = "{$range['start']}-{$range['end']}/$entity_length";
    $partheaders['Content-Length'] = $range['end'] - $range['start'] + 1;
    foreach ($partheaders as $header => $value)
      echo "$header: $value\r\n";
    echo "\r\n";
    if ( $entity_length === $range['end'] + 1 )
      fpassthru($entity);
    else {
      $size = $range['end'] - $range['start'] + 1;
      while ($size && !feof($entity)) {
        $buffer = fread($entity, ($size < DAV::$CHUNK_SIZE) ? $size : DAV::$CHUNK_SIZE);
        $size -= strlen($buffer);
        echo $buffer;
      }
      $current_position = $range['end'] + 1 - $size;
      if ($size)
        trigger_error(
          var_export( debug_backtrace(), true ),
          E_USER_WARNING
        );
    }
  }
  echo "\r\n--{$multipart_separator}--\r\n";
  fclose($entity);
}
    
    
} // class

