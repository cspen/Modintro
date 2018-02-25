<?php
// Default media type offered by this server
define("MEDIA_DEFAULT", "application/json");

// Acceptable request header values
$MEDIA_TYPES = array('application/json', 'text/xml', 'application/xml', 'text/html');
$MEDIA_WILD = array('application/json' => 'application/*', 'text/html' => 'text/*');

$LANGUAGE = array('en-US', 'en');
$CHARSET = "utf-8";




?>