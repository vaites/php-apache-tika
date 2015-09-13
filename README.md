PHP Apache Tika
===============

This tool provides [Apache Tika](https://tika.apache.org) bindings for PHP,
allowing to extract text and metadata from documents, images and other formats. 

Unlike other solutions [tika-server](http://wiki.apache.org/tika/TikaJAXRS)
are used, increasing speed (no need to run JVM on each request).

Features
--------

* Simple class interface to Apache Tika features:
    * Text and HTML extraction
    * Metadata extraction
    * OCR recognition
* Standarized metadata for documents
* Support for local and remote resources
* No heavyweight library dependencies

Requirements
------------

* PHP 5.4 or greater
* Apache Tika 1.7 or greater
* Oracle Java or OpenJDK 
    * Java 6 for Tika up to 1.9
    * Java 7 for Tika 1.10 or greater
* [Tesseract](https://github.com/tesseract-ocr/tesseract) (optional for OCR recognition)
    

Installation
------------

Install using composer:

    composer require vaites/php-apache-tika

If you want to use OCR you must install [Tesseract](https://github.com/tesseract-ocr/tesseract):

* **Fedora/CentOS**: `sudo yum install tesseract` (use dnf instead of yum on Fedora 22 or greater)
* **Debian/Ubuntu**: `sudo apt-get install tesseract-ocr`
* **Mac OS X**: `brew install tesseract` (using [Homebrew](http://brew.sh))


Usage
-----

Start Apache Tika server with [caution](http://www.openwall.com/lists/oss-security/2015/08/13/5):

    java -jar tika-server-1.10.jar

Use the class to extract text from documents:

    $client = \Vaites\ApacheTika\Client::make($host, $port);
    $language = $client->getLanguage('/path/to/your/document');
    $metadata = $client->getMetadata('/path/to/your/document');

    $html = $client->getHTML('/path/to/your/document');
    $text = $client->getText('/path/to/your/document');

Or use to extract text from images:

    $client = \Vaites\ApacheTika\Client::make($host, $port);
    $metadata = $client->getMetadata('/path/to/your/image');

    $text = $client->getText('/path/to/your/image');
    