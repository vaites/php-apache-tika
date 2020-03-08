# v1.0.0

* Drop support for PHP 5
* Drop support for Apache Tika 1.14 and lower
* Added type declarations and return types

# v0.9.0

* Added Client::setEncoding() to avoid encoding problems using app mode
* Added _Troubleshooting_ section to the README.md

# v0.8.0

* Added option to disable append on Client::setCallback() to save memory

# v0.7.2

 * Tested up to Apache Tika 1.23
 * Spawn scripts 'autodetects' if module java.se.ee is required

# v0.7.1

* Tested up to version 1.21

# v0.7.0

* Added recursive metadata support (thanks to @svaningelgem)
* Added encoding to `DocumentMetadata` (thanks to @svaningelgem)
* Fixed compatibility with Windows on command line mode (thanks to @GAMESTER90)
* Improve web client extensibility
* Abstracted cache layer
* Tested up to version 1.21

# v0.6.0

* Support to set host and port using an URL (thanks to @mpdude)
* Reduced memory usage (thanks to @JBleijenberg)
* Added `Client::prepare()` to avoid checks, saving HTTP calls and filesystem accesses
* Tested up to Apache Tika 1.20

# v0.5.1

* Tested up to Apache Tika 1.19.1
* Tested up to PHP 7.3

# v0.5.0

* Added Client::isVersionSupported() method
* Added Client::getSupportedMIMETypes() method
* Added Client::getAvailableDetectors() method
* Added Client::getAvailableParsers() method
* Added Client::getOption() and Client::getOptions() methods to web client
* Added Client::getTimeout() and Client::getTimeout() methods to web client
* Enhanced spawn.sh script