PHP Apache Tika
===============

This class provides [Apache Tika](https://tika.apache.org) bindings PHP,
allowing to extract metadata, text, HTML and more.

Unlike other solutions [tika-server](http://wiki.apache.org/tika/TikaJAXRS)
are used, increasing speed (no need to run JVM on each request).

Features
--------

* Simple class interface to Apache Tika features:
    * Extract metadata
    * Text and HTML extraction
    * Language detector
* Standarized metadata
* Support for local and remote resources
* No heavyweight library dependencies
* Compatible with PHP 5.4 or greater

Installation
------------

Install using composer:

    composer require vaites/php-apache-tika


Usage
-----

Start Apache Tika server with [caution](http://www.openwall.com/lists/oss-security/2015/08/13/5):

    java -jar tika-server-1.10.jar


Use the class:

    $client = Vaites\\ApacheTika\\Client::make($host, $port);
    $client->getLanguage('/path/to/your/document');
    $client->getMetadata('/path/to/your/document');

    $client->getHTML('/path/to/your/document');
    $client->getText('/path/to/your/document');


TO-DO
-----

* Laravel integration (File and Str classes)
* More metadata classes (audio, video, image, packages...)