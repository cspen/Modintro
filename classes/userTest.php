<?php

require('../utilities/appSettings.php');
require 'AcceptMediaType.php';

if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	global $LANGUAGE;
	$langs = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	
	$f = FALSE;
	foreach($langs as $lang) {
		$l = explode(";", $lang);
		 echo $l[0].'<br>';
		if(in_array(trim($l[0]), $LANGUAGE)) {
			$f = TRUE;
			break;
		}
	}
	// This method currently does nothing but
	// can be modified to use mulitple languages
}

/*

// Check media type client expects
if(!empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
	global $CHARSET;
	$sets = explode(",", $_SERVER['HTTP_ACCEPT_CHARSET']);
	
	// This service only uses utf-8
	$f = FALSE;
	foreach($sets as $set) { 
		$s = explode(";", $set);echo $s[0].' ';
		if(trim($s[0]) === $CHARSET || trim($s[0] === "*")) {
			$f = TRUE;
			break;
		}
	}
	
	if(!$f) {
		header('HTTP/1.1 406 Not Acceptable');
		exit;
	}    
} */
?>