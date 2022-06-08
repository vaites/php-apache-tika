<?php require('./vendor/autoload.php');

$version = getenv('APACHE_TIKA_VERSION') ?: '2.4.0';
$type = $GLOBALS['argv'][1] ?? null;
$client = tika($type === '--cli' ? "./bin/tika-app-$version.jar" : '127.0.0.1');

$metadata = $client->getMetadata('./samples/sample1.doc');