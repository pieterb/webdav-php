v1.0.3
- Changed the implementation of a PUT request without a range header; if no content-type is specified by the request header, then any already existing getcontenttype property will be cleared

v1.0.2
- Specified an extra dependency in composer.json

v1.0.1
- If you are using Composer to load the library, just run \DAV::bootstrap() otherwise include lib\bootstrap.php (and don't run \DAV::bootstrap() seperately)
- Set the configuration with \DAV::setDebugFile() instead of the config.ini

API changes made while creating unit tests:
* To load the library, you need to include /lib/bootstrap.php (instead of /lib/dav.php)
* DAV::abs2uri() is changed to DAV::path2uri() and now always returns an uri (if you supply a relative path instead of an absolute path, it prefixes the path with the current request URI)
* DAV::$SUPPORTED_PROPERTIES is now private and can be retrieved by calling DAV::getSupported_Properties()
* DAV::$PATH is now private and can be retrieved by calling DAV::getPath()
* DAV::$CONFIG is now private and can be retrieved by calling DAV::getConfig()
* DAV_Resource::set_ishidden() and DAV_Resource::prop_ishidden() are removed, effectively no longer supporting the DAV: ishidden property
