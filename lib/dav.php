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
 * File documentation (who cares)
 * @package DAV
 */

// PHP messages destroy XML output -> switch them off.
ini_set('display_errors', 0);
// magic quotes spoil everything.
if (ini_get('magic_quotes_gpc'))
  trigger_error('Please disable magic_quotes_gpc first.', E_USER_ERROR);

// We use autoloading of classes:
set_include_path(
  dirname(__FILE__) . PATH_SEPARATOR . get_include_path()
);
spl_autoload_register();


/**
 * Just a namespace for constants and helper functions
 * @package DAV
 */
class DAV {


// Various possible values of the RFC4918 Depth: HTTP header:
const DEPTH_0   = '0';
const DEPTH_1   = '1';
const DEPTH_INF = 'infinity';


// Various possible lock scopes. A lock can be EXCLUSIVE xor SHARED, not BOTH.
// But a resource may support neither, either, or both.
const LOCKSCOPE_NONE      = 0;
const LOCKSCOPE_EXCLUSIVE = 1;
const LOCKSCOPE_SHARED    = 2;
const LOCKSCOPE_BOTH      = 3;


//const CAPABILITY_GET_RANGE = 0x0001;
//const CAPABILITY_PUT_RANGE = 0x0002;
//const CAPABILITY_LOCK      =  1; // Locking as per RFC4918
// The following capabilities are not yet implemented:
//const CAPABILITY_ACL       =  2; // ACL as per RFC3744
//const CAPABILITY_SEARCH    =  4; // SEARCH as per RFC5323
//const CAPABILITY_REDIRECT  =  8; // *REDIRECTREF as per RFC4437
//const CAPABILITY_ORDERED   = 16; // Ordered collections as per RFC3648
//const CAPABILITY_BIND      = 32; // BIND as per RFC5842


// Some properties that are defined in RFC4918:
const PROP_CREATIONDATE       = 'DAV: creationdate';
const PROP_DISPLAYNAME        = 'DAV: displayname';
const PROP_GETCONTENTLANGUAGE = 'DAV: getcontentlanguage';
const PROP_GETCONTENTLENGTH   = 'DAV: getcontentlength';
const PROP_GETCONTENTTYPE     = 'DAV: getcontenttype';
const PROP_GETETAG            = 'DAV: getetag';
const PROP_GETLASTMODIFIED    = 'DAV: getlastmodified';
const PROP_LOCKDISCOVERY      = 'DAV: lockdiscovery';
const PROP_RESOURCETYPE       = 'DAV: resourcetype';
const PROP_SUPPORTEDLOCK      = 'DAV: supportedlock';
// Some other common but undocumented properties:
const PROP_EXECUTABLE         = 'http://apache.org/dav/props/ executable';
const PROP_EXECUTABLE2        = 'DAV: executable';


// RFC3744 Principal properties:
const PROP_ALTERNATE_URI_SET = 'DAV: alternate-URI-set';
const PROP_PRINCIPAL_URL     = 'DAV: principal-URL';
const PROP_GROUP_MEMBER_SET  = 'DAV: group-member-set';
const PROP_GROUP_MEMBERSHIP  = 'DAV: group-membership';

// RFC3744 Access control properties:
const PROP_OWNER                      = 'DAV: owner';
const PROP_GROUP                      = 'DAV: group';
const PROP_SUPPORTED_PRIVILEGE_SET    = 'DAV: supported-privilege-set';
const PROP_CURRENT_USER_PRIVILEGE_SET = 'DAV: current-user-privilege-set';
const PROP_ACL                        = 'DAV: acl';
const PROP_ACL_RESTRICTIONS           = 'DAV: acl-restrictions';
const PROP_INHERITED_ACL_SET          = 'DAV: inherited-acl-set';
const PROP_PRINCIPAL_COLLECTION_SET   = 'DAV: principal-collection-set';


// RFC3253 REPORT related properties:
const PROP_SUPPORTED_REPORT_SET       = 'DAV: supported-report-set';

// RFC5397 Access control property:
const PROP_CURRENT_USER_PRINCIPAL     = 'DAV: current-user-principal';


public static $WEBDAV_PROPERTIES = array(
  self::PROP_CREATIONDATE         => 'creationdate',
  self::PROP_DISPLAYNAME          => 'displayname',
  self::PROP_GETCONTENTLANGUAGE   => 'getcontentlanguage',
  self::PROP_GETCONTENTLENGTH     => 'getcontentlength',
  self::PROP_GETCONTENTTYPE       => 'getcontenttype',
  self::PROP_GETETAG              => 'getetag',
  self::PROP_GETLASTMODIFIED      => 'getlastmodified',
  self::PROP_LOCKDISCOVERY        => 'lockdiscovery',
  self::PROP_RESOURCETYPE         => 'resourcetype',
  self::PROP_SUPPORTEDLOCK        => 'supportedlock',
  self::PROP_SUPPORTED_REPORT_SET => 'supported_report_set',
  self::PROP_EXECUTABLE           => 'executable',
  self::PROP_EXECUTABLE2          => 'executable',
);


public static $PRINCIPAL_PROPERTIES = array(
  self::PROP_ALTERNATE_URI_SET => 'alternate_URI_set',
  self::PROP_PRINCIPAL_URL     => 'principal_URL',
  self::PROP_GROUP_MEMBER_SET  => 'group_member_set',
  self::PROP_GROUP_MEMBERSHIP  => 'group_membership',
);
  

public static $ACL_PROPERTIES = array(
  self::PROP_OWNER                      => 'owner',
  self::PROP_GROUP                      => 'group',
  self::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
  self::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
  self::PROP_ACL                        => 'acl',
  self::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
  self::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
  self::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
  // RFC5397 Access control property:
  self::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
);


public static $SUPPORTED_PROPERTIES = array(
  // RFC4918:
  self::PROP_CREATIONDATE       => 'creationdate',
  self::PROP_DISPLAYNAME        => 'displayname',
  self::PROP_GETCONTENTLANGUAGE => 'getcontentlanguage',
  self::PROP_GETCONTENTLENGTH   => 'getcontentlength',
  self::PROP_GETCONTENTTYPE     => 'getcontenttype',
  self::PROP_GETETAG            => 'getetag',
  self::PROP_GETLASTMODIFIED    => 'getlastmodified',
  self::PROP_LOCKDISCOVERY      => 'lockdiscovery',
  self::PROP_RESOURCETYPE       => 'resourcetype',
  self::PROP_SUPPORTEDLOCK      => 'supportedlock',
  self::PROP_EXECUTABLE         => 'executable',
  self::PROP_EXECUTABLE2        => 'executable',
  // RFC3744 principal properties:
  self::PROP_ALTERNATE_URI_SET => 'alternate_URI_set',
  self::PROP_PRINCIPAL_URL     => 'principal_URL',
  self::PROP_GROUP_MEMBER_SET  => 'group_member_set',
  self::PROP_GROUP_MEMBERSHIP  => 'group_membership',
  // RFC3744 Access Control properties:
  self::PROP_OWNER                      => 'owner',
  self::PROP_GROUP                      => 'group',
  self::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
  self::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
  self::PROP_ACL                        => 'acl',
  self::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
  self::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
  self::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
  // RFC3253 REPORT related properties:
  self::PROP_SUPPORTED_REPORT_SET       => 'supported_report_set',
  // RFC5397 Access control property:
  self::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
);

public static $PROTECTED_PROPERTIES = array(
  // RFC4918:
  self::PROP_CREATIONDATE               => 'creationdate',
  self::PROP_GETCONTENTLENGTH           => 'getcontentlength',
  self::PROP_GETETAG                    => 'getetag',
  self::PROP_GETLASTMODIFIED            => 'getlastmodified',
  self::PROP_LOCKDISCOVERY              => 'lockdiscovery',
  self::PROP_RESOURCETYPE               => 'resourcetype',
  self::PROP_SUPPORTEDLOCK              => 'supportedlock',
  // RFC3744 Principal properties
  self::PROP_ALTERNATE_URI_SET          => 'alternate_URI_set',
  self::PROP_PRINCIPAL_URL              => 'principal_URL',
  self::PROP_GROUP_MEMBERSHIP           => 'group_membership',
  // RFC3744 Access control properties
  self::PROP_SUPPORTED_PRIVILEGE_SET    => 'supported_privilege_set',
  self::PROP_CURRENT_USER_PRIVILEGE_SET => 'current_user_privilege_set',
  self::PROP_ACL                        => 'acl',
  self::PROP_ACL_RESTRICTIONS           => 'acl_restrictions',
  self::PROP_INHERITED_ACL_SET          => 'inherited_acl_set',
  self::PROP_PRINCIPAL_COLLECTION_SET   => 'principal_collection_set',
  // RFC3253 REPORT related properties:
  self::PROP_SUPPORTED_REPORT_SET       => 'supported_report_set',
  // RFC5397 Access control property:
  self::PROP_CURRENT_USER_PRINCIPAL     => 'current_user_principal',
);


// All pre- and postconditions that are defined in RFC4918:
const COND_CANNOT_MODIFY_PROTECTED_PROPERTY = 'cannot-modify-protected-property';
const COND_LOCK_TOKEN_MATCHES_REQUEST_URI   = 'lock-token-matches-request-uri';
const COND_LOCK_TOKEN_SUBMITTED             = 'lock-token-submitted';
const COND_NO_CONFLICTING_LOCK              = 'no-conflicting-lock';
const COND_NO_EXTERNAL_ENTITIES             = 'no-external-entities';
const COND_PRESERVED_LIVE_PROPERTIES        = 'preserved-live-properties';
const COND_PROPFIND_FINITE_DEPTH            = 'propfind-finite-depth';

// All pre- and postconditions that are defined in RFC3744:
const COND_ALLOWED_PRINCIPAL               = 'allowed-principal';
const COND_DENY_BEFORE_GRANT               = 'deny-before-grant';
const COND_GRANT_ONLY                      = 'grant-only';
const COND_LIMITED_NUMBER_OF_ACES          = 'limited-number-of-aces';
const COND_MISSING_REQUIRED_PRINCIPAL      = 'missing-required-principal';
const COND_NEED_PRIVILEGES                 = 'need-privileges';
const COND_NO_ABSTRACT                     = 'no-abstract';
const COND_NO_ACE_CONFLICT                 = 'no-ace-conflict';
const COND_NO_INHERITED_ACE_CONFLICT       = 'no-inherited-ace-conflict';
const COND_NO_INVERT                       = 'no-invert';
const COND_NO_PROTECTED_ACE_CONFLICT       = 'no-protected-ace-conflict';
const COND_NOT_SUPPORTED_PRIVILEGE         = 'not-supported-privilege';
const COND_NUMBER_OF_MATCHES_WITHIN_LIMITS = 'number-of-matches-within-limits';
const COND_RECOGNIZED_PRINCIPAL            = 'recognized-principal';

/**
 * All preconditions and postconditions mentioned in the RFCs
 * @var array
 */
public static $CONDITIONS = array(
  // RFC4918:
  self::COND_CANNOT_MODIFY_PROTECTED_PROPERTY => self::COND_CANNOT_MODIFY_PROTECTED_PROPERTY,
  self::COND_LOCK_TOKEN_MATCHES_REQUEST_URI   => self::COND_LOCK_TOKEN_MATCHES_REQUEST_URI,
  self::COND_LOCK_TOKEN_SUBMITTED             => self::COND_LOCK_TOKEN_SUBMITTED,
  self::COND_NO_CONFLICTING_LOCK              => self::COND_NO_CONFLICTING_LOCK,
  self::COND_NO_EXTERNAL_ENTITIES             => self::COND_NO_EXTERNAL_ENTITIES,
  self::COND_PRESERVED_LIVE_PROPERTIES        => self::COND_PRESERVED_LIVE_PROPERTIES,
  self::COND_PROPFIND_FINITE_DEPTH            => self::COND_PROPFIND_FINITE_DEPTH,
  // RFC3744:
  self::COND_ALLOWED_PRINCIPAL                => self::COND_ALLOWED_PRINCIPAL,
  self::COND_DENY_BEFORE_GRANT                => self::COND_DENY_BEFORE_GRANT,
  self::COND_GRANT_ONLY                       => self::COND_GRANT_ONLY,
  self::COND_LIMITED_NUMBER_OF_ACES           => self::COND_LIMITED_NUMBER_OF_ACES,
  self::COND_MISSING_REQUIRED_PRINCIPAL       => self::COND_MISSING_REQUIRED_PRINCIPAL,
  self::COND_NEED_PRIVILEGES                  => self::COND_NEED_PRIVILEGES,
  self::COND_NO_ABSTRACT                      => self::COND_NO_ABSTRACT,
  self::COND_NO_ACE_CONFLICT                  => self::COND_NO_ACE_CONFLICT,
  self::COND_NO_INHERITED_ACE_CONFLICT        => self::COND_NO_INHERITED_ACE_CONFLICT,
  self::COND_NO_INVERT                        => self::COND_NO_INVERT,
  self::COND_NO_PROTECTED_ACE_CONFLICT        => self::COND_NO_PROTECTED_ACE_CONFLICT,
  self::COND_NOT_SUPPORTED_PRIVILEGE          => self::COND_NOT_SUPPORTED_PRIVILEGE,
  self::COND_NUMBER_OF_MATCHES_WITHIN_LIMITS  => self::COND_NUMBER_OF_MATCHES_WITHIN_LIMITS,
  self::COND_RECOGNIZED_PRINCIPAL             => self::COND_RECOGNIZED_PRINCIPAL,
);


const REPORT_EXPAND_PROPERTY               = 'expand-property';
const REPORT_ACL_PRINCIPAL_PROP_SET        = 'acl-principal-prop-set';
const REPORT_PRINCIPAL_MATCH               = 'principal-match';
const REPORT_PRINCIPAL_PROPERTY_SEARCH     = 'principal-property-search';
const REPORT_PRINCIPAL_SEARCH_PROPERTY_SET = 'principal-search-property-set';


public static $REPORTS = array(
  self::REPORT_EXPAND_PROPERTY               => 'expand_property',
  self::REPORT_ACL_PRINCIPAL_PROP_SET        => 'acl_principal_prop_set',
  self::REPORT_PRINCIPAL_MATCH               => 'principal_match',
  self::REPORT_PRINCIPAL_PROPERTY_SEARCH     => 'principal_property_search',
  self::REPORT_PRINCIPAL_SEARCH_PROPERTY_SET => 'principal_search_property_set',
);


public static $CHUNK_SIZE = 67108864; // 64MiB


/**
 * Initialized at bottom of file.
 * @var string
 */
public static $PATH;


/**
 * Defines if lock tokens are hidden in lockdiscovery properties.
 * @var boolean
 */
public static $HIDELOCKTOKENS = true;


/**
 * @var DAV_Registry
 */
public static $REGISTRY = null;


/**
 * @var DAV_Lock_Provider
 */
public static $LOCKPROVIDER = null;


/**
 * @var DAVACL_ACL_Provider
 */
public static $ACLPROVIDER = null;


/**
 * An array of all statetokens submitted by the user in the If: header.
 * @var array <code>array( <stateToken> => <stateToken>, ... ></code>
 */
public static $SUBMITTEDTOKENS = array();


/**
 * Remake of PHP's var_dump().
 * Returns the output instead of outputting it.
 * @param mixed $var
 * @return string
 */
public static function var_dump($var) {
  ob_start();
  var_dump($var);
  return ob_get_clean();
}


/**
 * Writes $string to some debug file.
 * @param mixed $data
 * @see DAV::$DEBUG_FILE
 */
public static function debug() {
  $data = '';
  foreach (func_get_args() as $arg)
    $data .= "\n" . ( is_string($arg) ? $arg : self::var_dump($arg) );
  $fh = fopen( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'debug.txt', 'a' );
  fwrite($fh, date('r') . ":$data\n\n");
  fclose ($fh);
}


public static function forbidden() {
  return ( !self::$ACLPROVIDER ||
           self::$ACLPROVIDER->user_prop_current_user_principal() ) ?
    self::HTTP_FORBIDDEN : self::HTTP_UNAUTHORIZED;
}


/**
 * @param string $property
 * @return string XML
 * @throws DAV_Status
 */
public static function expand($property) {
  $property = explode(' ', $property);
  if (2 != count($property))
    throw new DAV_Status(DAV::HTTP_INTERNAL_SERVER_ERROR);
  return ('DAV:' == $property[0] )
    ? ( 'D:' . $property[1] )
    : ( $property[1] . " xmlns=\"{$property[0]}\"" );
}


/**
 * Adds a trailing slash if there isn't one.
 * @internal
 * @param string $string
 * @return string
 */
public static function slashify($string) {
  if ( '/' !== substr($string, -1) )
    $string .= '/';
  return $string;
}


/**
 * Removes a trailing slash if possible.
 * @internal
 * @param string $string
 * @return string
 */
public static function unslashify($string) {
  if ( '/' === substr($string, -1) &&
       $string !== '/' )
    $string = substr($string, 0, -1);
  return $string;
}


/**
 * @internal
 * @param DOMNode $node
 * @param DAV_Namespaces $p_namespaces
 * @return string
 */
public static function recursiveSerialize(
  $node, $p_namespaces = null
) {
  switch ($node->nodeType) {
    case XML_ELEMENT_NODE:
      break;
    case XML_COMMENT_NODE:
      return  '<!--' . str_replace( '-->', '--&lt;', $node->nodeValue ) . '-->';
    case XML_PI_NODE:
      return
        "<?{$node->target} " . str_replace( '?>', '?&lt;', $node->data ) . '?>';
    case XML_CDATA_SECTION_NODE:
      return '<![CDATA[' . $node->data .']]>';
    default:
      return DAV::xmlescape($node->nodeValue);
  }
  $namespaces = is_null( $p_namespaces ) ?
    new DAV_Namespaces() : $p_namespaces;

  $elementprefix = $namespaces->prefix( $node->namespaceURI );
  $elementlocalName = $node->localName;
  $retval = "<$elementprefix$elementlocalName";
  
  // Attributes handling:
  $elementattributes = $node->attributes;
  $attributes = array();
  for ($i = 0; $elementattribute = $elementattributes->item($i); $i++) {
    // The next if-statement is probably not necessary, because the DOMXML parser
    // doesn't return these attributes as the DOM tree anyway.
    if ( $elementattribute->prefix === 'xmlns' or
         $elementattribute->namespaceURI == '' &&
         $elementattribute->localName === 'xmlns' )
      continue;
    $p = $namespaces->prefix( $elementattribute->namespaceURI );
    $attributes[ $p . $elementattribute->localName ] =
      $elementattribute->value;
  }
  foreach ($attributes as $key => $value)
    $retval .= " $key=\"" . DAV::xmlescape( $value, true ) . '"';
    
  // The contents of the element:
  $childXML = '';
  for ($i = 0; $childNode = $node->childNodes->item($i); $i++)
    $childXML .= self::recursiveSerialize($childNode, $namespaces);
    
  if ( is_null( $p_namespaces ) )
    $retval .= $namespaces->toXML();
    
  if ( $childXML !== '')
    $retval .= ">$childXML</$elementprefix$elementlocalName>";
  else
    $retval .= '/>';
  return $retval;
}


/**
 * Translates a URL into a DAV::$PATH-like path, if possible.
 * @param string $url the URL to translate
 * @param bool $fail Should this method fail if the URL is outside this server's
 * realm?
 * @return string
 * @throws DAV_Status
 */
public static function parseURI($url, $fail = true) {
  static $URI_REGEXP = null;
  if (is_null($URI_REGEXP)) {
    $URI_REGEXP = '@^(?:http';
    if (!empty($_SERVER['HTTPS'])) $URI_REGEXP .= 's';
    $URI_REGEXP .= '://';
    if (isset($_SERVER['PHP_AUTH_USER']))
      $URI_REGEXP .= '(?:' . preg_quote( rawurlencode($_SERVER['PHP_AUTH_USER']) ) . '\\@)?';
    $URI_REGEXP .= preg_quote($_SERVER['SERVER_NAME'], '@') . '(?::' . $_SERVER['SERVER_PORT'] . ')';
    if ( empty($_SERVER['HTTPS']) &&  80 == $_SERVER['SERVER_PORT'] or
        !empty($_SERVER['HTTPS']) && 443 == $_SERVER['SERVER_PORT'] )
      $URI_REGEXP .= '?';
    $URI_REGEXP .= ')?(/[^?#]*)(?:\\?[^#]*)?(?:#.*)?$@';
  }
  if ( preg_match( $URI_REGEXP, $url, $matches ) ) {
    $retval = preg_replace( '@//+@', '/', $matches[1] );
    if ( preg_match( '@(?:^|/)\\.\\.?(?:$|/)@', $retval ) ||
         preg_match( '@%(?:0|1|2f|7f)@i', $retval ) )
      throw new DAV_Status(
        DAV::HTTP_FORBIDDEN,
        'This hacking attempt will be investigated.'
      );
    return $retval;
  }
  if ($fail)
    throw new DAV_Status(
      DAV::HTTP_BAD_REQUEST,
      "Resource $url is not within the scope of this server."
    );
  return $url;
}


/**
 * @param string $utf8text
 * @param bool $isAttribute
 * @return string the utf8text, escaped for use in an XML text or attribute element.
 */
public static function xmlescape($utf8text, $isAttribute = false) {
  $retval = htmlspecialchars(
    $utf8text, $isAttribute ? ENT_QUOTES : ENT_NOQUOTES, 'UTF-8'
  );
  if (empty($retval) && !empty($utf8text)) {
    DAV::debug($utf8text . rawurlencode($utf8text));
    return htmlspecialchars(
      $utf8text,
      ENT_IGNORE | ( $isAttribute ? ENT_QUOTES : ENT_NOQUOTES ),
      'UTF-8'
    );
  }
  return $retval;
}


/**
 * Yet another URL encoder.
 * @param string $path
 * @return string
 * @deprecated PHP's built-in rawurlencode is correctly implemented since 5.3.
 */
public static function rawurlencode($path) {
  $newurl = '';
  for ($i = 0; $i < strlen($path); $i++) {
    $ord = ord($path[$i]);
    if ( $ord >= ord('a') && $ord <= ord('z') ||
         $ord >= ord('A') && $ord <= ord('Z') ||
         $ord >= ord('0') && $ord <= ord('9') ||
         strpos( '/-_.~', $path[$i] ) !== false )
         // Strictly spoken, the tilde ~ should be encoded as well, but I
         // don't do that. This makes sure URL's like http://some.com/~user/
         // don't get mangled, at the risk of problems during transport.
      $newurl .= $path[$i];
    else
      $newurl .= sprintf('%%%2X', $ord);
  }
  return $newurl;
}
  
  
/**
 * Translate an absolute path to a full URI.
 * @param string $absolutePath
 * @return string
 */
public static function abs2uri( $absolutePath ) {
  return ('/' == $absolutePath[0])
    ? self::urlbase() . $absolutePath
    : $absolutePath;
}
  
  
/**
 * Returns the base URI.
 * The base URI is 'protocol://server.name:port'
 * @return string
 */
public static function urlbase() {
	static $URLBASE = null;
  if ( is_null( $URLBASE ) ) {
    $URLBASE = empty($_SERVER['HTTPS']) ?
      'http://' : 'https://';
    $URLBASE .= $_SERVER['SERVER_NAME'];
    if ( !empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] != 443 or
          empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] != 80 )
      $URLBASE .= ":{$_SERVER['SERVER_PORT']}";
  }
  return $URLBASE;
}
  
  
/**
 * The default xml header.
 * @internal
 * @return string The xml header with proper version and encoding
 */
public static function xml_header($encoding = 'utf-8', $version = '1.0') {
  return "<?xml version=\"$version\" encoding=\"$encoding\"?>\n";
}


/**
 * Outputs HTTP/1.1 headers.
 * @param $properties array|string An array of headers to print, e.g.
 * <code>array( 'Content-Language' => 'en-us' )</code> If there's a
 * key «status» in the array, it is used for the 'HTTP/1.X ...'
 * status header, e.g.<code>array(
 *   'status'       => DAV::HTTP_CREATED,
 *   'Content-Type' => 'text/plain'
 * )</code> If <var>$properties</var> is a string, it is taken as the
 * Content-Type, e.g.<code>$rest->header('text/plain')</code> is exactly
 * equivalent to
 * <code>$rest->header( array( 'Content-Type' => 'text/plain' ) );</code>
 * @return void
 * @see status_code()
 */
public static function header($properties) {
  if (is_string($properties))
    $properties = array( 'Content-Type' => $properties );
  if (isset($_SERVER['HTTP_ORIGIN']))
    $properties['Access-Control-Allow-Origin'] = $_SERVER['HTTP_ORIGIN'];
  $status = null;
  if (isset($properties['status'])) {
    $status = $properties['status'];
    unset( $properties['status'] );
  }
  // RFC2616 §14.16
  // A server sending a response with status code 416 (Requested range not
  // satisfiable) SHOULD include a Content-Range field with a byte-range-
  // resp-spec of "*".
  if ( self::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE == (int)$status &&
       !isset( $properties['Content-Range'] ) )
    $properties['Content-Range'] = 'bytes */*';
  if (isset($properties['Location']))
    $properties['Location'] = self::abs2uri($properties['Location']);
  foreach($properties as $key => $value)
    header("$key: $value");
  if ($status !== null)
    header( $_SERVER['SERVER_PROTOCOL'] . ' ' . self::status_code($status) );
}
  
  
/**
 * Redirects to a URL.
 * @param int $status
 * @param string $url
 */
public static function redirect($status, $uri) {
  self::header( array(
    'status' => $status,
    'Content-Type' => 'text/plain; charset=US-ASCII',
    'Location' => self::abs2uri($uri)
  ));
  echo $uri;
}


/**
 * Returns an HTTP date as per HTTP/1.1 definition.
 * @param int $timestamp A unix timestamp
 * @return string
 */
public static function httpDate($timestamp) {
  return gmdate( 'D, d M Y H:i:s \\G\\M\\T', $timestamp );
}


/**
 * @param $uri string
 * @return boolean
 */
public static function isValidURI($uri) {
  return preg_match('@^[a-z]+:(?:%[a-fA-F0-9]{2}|[-\\w.~:/?#\\[\\]\\@!$&\'()*+,;=]+)+$@', $uri);
}


/**
 * @param string $path
 * @return mixed one of the following:
 * - DAV_Element_href of the lockroot of the missing token
 * - null if no lock was found.
 */
public static function assertLock($path) {
  if ( !self::$LOCKPROVIDER ) return null;
  if ( ( $lock = self::$LOCKPROVIDER->getlock($path) ) &&
       !isset( self::$SUBMITTEDTOKENS[$lock->locktoken] ) ) {
    $lockroot = DAV::$REGISTRY->resource($lock->lockroot);
    if (!$lockroot)
      throw new DAV_Status(DAV::HTTP_INTERNAL_SERVER_ERROR);
    return new DAV_Element_href(
      $lockroot->isVisible() ?
      $lock->lockroot : '/undisclosed-resource'
    );
  }
  return null;
}


/**
 * @param string $path
 * @return mixed one of the following:
 * - DAV_Element_href of the lockroot of the missing token
 * - null if no lock was found.
 */
public static function assertMemberLocks($path) {
  if ( !self::$LOCKPROVIDER ) return null;
  $locks = DAV::$LOCKPROVIDER->memberLocks( $path );
  foreach ($locks as $token => $lock)
    if ( !isset( self::$SUBMITTEDTOKENS[$token] ) )
      return new DAV_Element_href(
        DAV::$REGISTRY->resource($lock->lockroot)->isVisible() ?
        $lock->lockroot : '/undisclosed-resource'
      );
  return null;
}


/*
 * Outputs HTTP/1.1 headers.
 * @param array|string $properties An array of headers to print, e.g.
 * <code>array( 'Content-Language' => 'en-us' )</code> If there's a
 * key «status» in the array, it is used for the 'HTTP/1.X ...'
 * status header, e.g.<code>array(
 *   'status'       => '201 Created',
 *   'Content-Type' => 'text/plain'
 * )</code>
 * @return void
 * @see self::status_code()
 */
//public static function header($properties) {
//  if (!isset($properties['status']))
//    $properties['status'] = DAV::HTTP_OK;
//  // This header doesn't seem to be documented anywhere...
//  //$properties['X-WebDAV-Status'] = DAV::status_code($properties['status']);
//  $properties['X-Dav-Powered-By'] = DAV_Server::inst()->POWERED_BY;
//  DAV::header($properties);
//}


/*
 * Calls self::error() and exits.
 * @return void This function never returns.
 * @see DAV::error()
 */
//public static function fatal() {
//  $args = func_get_args();
//  call_user_func_array( array('DAV', 'error'), $args );
//  exit;
//}


const HTTP_CONTINUE                        = 100;
const HTTP_SWITCHING_PROTOCOLS             = 101;
const HTTP_PROCESSING                      = 102; // A WebDAV extension
const HTTP_OK                              = 200;
const HTTP_CREATED                         = 201;
const HTTP_ACCEPTED                        = 202;
const HTTP_NON_AUTHORITATIVE_INFORMATION   = 203; // HTTP/1.1 only
const HTTP_NO_CONTENT                      = 204;
const HTTP_RESET_CONTENT                   = 205;
const HTTP_PARTIAL_CONTENT                 = 206;
const HTTP_MULTI_STATUS                    = 207; // A WebDAV extension
const HTTP_MULTIPLE_CHOICES                = 300;
const HTTP_MOVED_PERMANENTLY               = 301;
const HTTP_FOUND                           = 302;
const HTTP_SEE_OTHER                       = 303; // HTTP/1.1 only
const HTTP_NOT_MODIFIED                    = 304;
const HTTP_USE_PROXY                       = 305; // HTTP/1.1 only
const HTTP_SWITCH_PROXY                    = 306;
const HTTP_TEMPORARY_REDIRECT              = 307; // HTTP/1.1 only
const HTTP_BAD_REQUEST                     = 400;
const HTTP_UNAUTHORIZED                    = 401;
const HTTP_PAYMENT_REQUIRED                = 402;
const HTTP_FORBIDDEN                       = 403;
const HTTP_NOT_FOUND                       = 404;
const HTTP_METHOD_NOT_ALLOWED              = 405;
const HTTP_NOT_ACCEPTABLE                  = 406;
const HTTP_PROXY_AUTHENTICATION_REQUIRED   = 407;
const HTTP_REQUEST_TIMEOUT                 = 408;
const HTTP_CONFLICT                        = 409;
const HTTP_GONE                            = 410;
const HTTP_LENGTH_REQUIRED                 = 411;
const HTTP_PRECONDITION_FAILED             = 412;
const HTTP_REQUEST_ENTITY_TOO_LARGE        = 413;
const HTTP_REQUEST_URI_TOO_LONG            = 414;
const HTTP_UNSUPPORTED_MEDIA_TYPE          = 415;
const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
const HTTP_EXPECTATION_FAILED              = 417;
const HTTP_UNPROCESSABLE_ENTITY            = 422; // A WebDAV/RFC2518 extension
const HTTP_LOCKED                          = 423; // A WebDAV/RFC2518 extension
const HTTP_FAILED_DEPENDENCY               = 424; // A WebDAV/RFC2518 extension
const HTTP_UNORDERED_COLLECTION            = 425; // A WebDAV RFC 3648 extension (obsolete)
const HTTP_UPGRADE_REQUIRED                = 426; // an RFC2817 extension
const HTTP_RETRY_WITH                      = 449; // a Microsoft extension
const HTTP_INTERNAL_SERVER_ERROR           = 500;
const HTTP_NOT_IMPLEMENTED                 = 501;
const HTTP_BAD_GATEWAY                     = 502;
const HTTP_SERVICE_UNAVAILABLE             = 503;
const HTTP_GATEWAY_TIMEOUT                 = 504;
const HTTP_HTTP_VERSION_NOT_SUPPORTED      = 505;
const HTTP_VARIANT_ALSO_VARIES             = 506; // an RFC2295 extension
const HTTP_INSUFFICIENT_STORAGE            = 507; // A WebDAV extension
const HTTP_BANDWIDTH_LIMIT_EXCEEDED        = 509;
const HTTP_NOT_EXTENDED                    = 510; // an RFC2774 extension


/**
 * @param $code int some code
 * @return string
 * @throws Exception if $code is unknown.
 */
public static function status_code($code) {
  static $STATUS_CODES = array(
    self::HTTP_CONTINUE                        => '100 Continue',
    self::HTTP_SWITCHING_PROTOCOLS             => '101 Switching Protocols',
    self::HTTP_PROCESSING                      => '102 Processing', // A WebDAV extension
    self::HTTP_OK                              => '200 OK',
    self::HTTP_CREATED                         => '201 Created',
    self::HTTP_ACCEPTED                        => '202 Accepted',
    self::HTTP_NON_AUTHORITATIVE_INFORMATION   => '203 Non-Authoritative Information', // HTTP/1.1 only
    self::HTTP_NO_CONTENT                      => '204 No Content',
    self::HTTP_RESET_CONTENT                   => '205 Reset Content',
    self::HTTP_PARTIAL_CONTENT                 => '206 Partial Content',
    self::HTTP_MULTI_STATUS                    => '207 Multi-Status', // A WebDAV extension
    self::HTTP_MULTIPLE_CHOICES                => '300 Multiple Choices',
    self::HTTP_MOVED_PERMANENTLY               => '301 Moved Permanently',
    self::HTTP_FOUND                           => '302 Found',
    self::HTTP_SEE_OTHER                       => '303 See Other', // HTTP/1.1 only
    self::HTTP_NOT_MODIFIED                    => '304 Not Modified',
    self::HTTP_USE_PROXY                       => '305 Use Proxy', // HTTP/1.1 only
    self::HTTP_SWITCH_PROXY                    => '306 Switch Proxy',
    self::HTTP_TEMPORARY_REDIRECT              => '307 Temporary Redirect', // HTTP/1.1 only
    self::HTTP_BAD_REQUEST                     => '400 Bad Request',
    self::HTTP_UNAUTHORIZED                    => '401 Unauthorized',
    self::HTTP_PAYMENT_REQUIRED                => '402 Payment Required',
    self::HTTP_FORBIDDEN                       => '403 Forbidden',
    self::HTTP_NOT_FOUND                       => '404 Not Found',
    self::HTTP_METHOD_NOT_ALLOWED              => '405 Method Not Allowed',
    self::HTTP_NOT_ACCEPTABLE                  => '406 Not Acceptable',
    self::HTTP_PROXY_AUTHENTICATION_REQUIRED   => '407 Proxy Authentication Required',
    self::HTTP_REQUEST_TIMEOUT                 => '408 Request Timeout',
    self::HTTP_CONFLICT                        => '409 Conflict',
    self::HTTP_GONE                            => '410 Gone',
    self::HTTP_LENGTH_REQUIRED                 => '411 Length Required',
    self::HTTP_PRECONDITION_FAILED             => '412 Precondition Failed',
    self::HTTP_REQUEST_ENTITY_TOO_LARGE        => '413 Request Entity Too Large',
    self::HTTP_REQUEST_URI_TOO_LONG            => '414 Request-URI Too Long',
    self::HTTP_UNSUPPORTED_MEDIA_TYPE          => '415 Unsupported Media Type',
    self::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE => '416 Requested Range Not Satisfiable',
    self::HTTP_EXPECTATION_FAILED              => '417 Expectation Failed',
    self::HTTP_UNPROCESSABLE_ENTITY            => '422 Unprocessable Entity', // A WebDAV/RFC2518 extension
    self::HTTP_LOCKED                          => '423 Locked', // A WebDAV/RFC2518 extension
    self::HTTP_FAILED_DEPENDENCY               => '424 Failed Dependency', // A WebDAV/RFC2518 extension
    self::HTTP_UNORDERED_COLLECTION            => '425 Unordered Collection', // A WebDAV RFC 3648 extension (obsolete)
    self::HTTP_UPGRADE_REQUIRED                => '426 Upgrade Required', // an RFC2817 extension
    self::HTTP_RETRY_WITH                      => '449 Retry With', // a Microsoft extension
    self::HTTP_INTERNAL_SERVER_ERROR           => '500 Internal Server Error',
    self::HTTP_NOT_IMPLEMENTED                 => '501 Not Implemented',
    self::HTTP_BAD_GATEWAY                     => '502 Bad Gateway',
    self::HTTP_SERVICE_UNAVAILABLE             => '503 Service Unavailable',
    self::HTTP_GATEWAY_TIMEOUT                 => '504 Gateway Timeout',
    self::HTTP_HTTP_VERSION_NOT_SUPPORTED      => '505 HTTP Version Not Supported',
    self::HTTP_VARIANT_ALSO_VARIES             => '506 Variant Also Negotiates', // an RFC2295 extension
    self::HTTP_INSUFFICIENT_STORAGE            => '507 Insufficient Storage (WebDAV)', // A WebDAV extension
    self::HTTP_BANDWIDTH_LIMIT_EXCEEDED        => '509 Bandwidth Limit Exceeded',
    self::HTTP_NOT_EXTENDED                    => '510 Not Extended', // an RFC2774 extension
  );
  $intcode = (int)$code;
  if (!isset($STATUS_CODES[$intcode]))
    throw new Exception("Unknown status code $code");
  return $STATUS_CODES[$intcode];
}


} // namespace DAV

