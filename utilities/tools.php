<?php
// require 'appSettings.php';
require '../../classes/AcceptMediaType.php';
require '../../classes/User.php';
require '../../utilities/DBConnection.php';

function processHeaders() {
	processHostHeader();
	processExpectHeader();
	processCharsetHeader();
	// processAcceptHeader();
	processLanguageHeader();
	
}

/**
 * Probably handled by server, but...
 * According to HTTP/1.1 section 14.23
 * https://tools.ietf.org/html/rfc2616#section-14.23
 */
function processHostHeader() {	
	if(!isset($_SERVER['HTTP_HOST'])) {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
}

/**
 * This server does not support the Expect header.
 * According to HTTP/1.1 section 14.20
 * https://tools.ietf.org/html/rfc2616#section-14.20
 */
function processExpectHeader() {
	if(isset($_SERVER['HTTP_EXPECT'])) {		
		header('HTTP/1.1 417 Expectation Failed');
		exit;
	}
}

/**
 * 
 * https://tools.ietf.org/html/rfc2616#section-14.2
 */
function processCharsetHeader() {
	// Check character encoding
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
	}
}

/**
 * 
 * https://tools.ietf.org/html/rfc2616#section-14.1
 */
function processAcceptHeader() {
	// Check media type client expects
	if(!empty($_SERVER['HTTP_ACCEPT'])) {
		global $MEDIA_TYPES, $MEDIA_WILD;
		$types = explode(",", $_SERVER['HTTP_ACCEPT']);
		$media = array();
		
		// First sort Accept header values by quality factor
		foreach($types as $type) {
			$m = new AcceptMediaType($type);
			$media[] = $m;
		}
		usort($media, "AcceptMediaType::compare");
		
		// Find acceptable media type or die
		$bestMatch = "";
		for($i = count($media)-1; $i >= 0; $i--) {
			$m = $media[$i];
			$t = $m->getMimeType();
			
			if(in_array($t, $MEDIA_TYPES)) {
				$bestMatch = $t;
				break;
			} elseif($key = array_search($t, $MEDIA_WILD)) {
				$bestMatch = $key;
				break;
			} elseif($t === "*/*") {
				$bestMatch = MEDIA_DEFAULT;
				break;
			}
		}
		
		if($bestMatch === "") {
			header('HTTP/1.1 406 Not Acceptable');
			exit;
		}
		return $bestMatch;
	} else {
		// No header sent - assume any media is acceptable
		return MEDIA_DEFAULT;
	}
}

/**
 * 
 * 
 * https://tools.ietf.org/html/rfc2616#section-14.4
 */
function processLanguageHeader() {
	// This method currently does nothing but
	// can be modified to use mulitple languages
	// Check language
	if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		global $LANGUAGE;
		$langs = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		
		$f = FALSE;
		foreach($langs as $lang) {
			$l = explode(";", $lang);
			// echo $l[0].'<br>';
			if(in_array(trim($l[0]), $LANGUAGE)) {
				$f = TRUE;
				break;
			}
		}
	}
}

function processIfMatchHeader() { 
	// Process E-tags
	if(isset($_SERVER['HTTP_IF_MATCH'])) {
		$etags = array_map('trim', explode(',', $_SERVER['HTTP_IF_MATCH']));
		return $etags;
	}
	return NULL;
}

function processIfNoneMatchHeader() {
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) { 
		$etags = array_map('clean', explode(',', $_SERVER['HTTP_IF_NONE_MATCH']));
		return $etags;
	}
	return NULL;
}

function processIfModifiedSinceHeader() {
	if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {			
		return strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	}
}

function processIfUnmodifiedSinceHeader() {
	if(isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])) {
		return strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']);
	}
}

/**
 * Remove whitespace, newlines, and parentheses from
 * the specified string.
 */
function clean($str) {
	return trim($str, " \t\n\r\0\x0B\"");
}


/**
 * The logic of the processConditionalHeaders method is explained in RFC 2616
 * at the following urls:
 *
 * If-Match - https://tools.ietf.org/html/rfc2616#section-14.24
 * If-None-Match -  https://tools.ietf.org/html/rfc2616#section-14.26
 * If-Modified-Since - https://tools.ietf.org/html/rfc2616#section-14.25
 * If-Unmodified-Since - https://tools.ietf.org/html/rfc2616#section-14.28
 */

function processConditionalHeaders($etag, $rowCount, $lastModified) { 
	$ifModSin = processIfModifiedSinceHeader();
	$ifUnmodSin = processIfUnmodifiedSinceHeader();
	$ifMatch = processIfMatchHeader();
	$ifNoneMatch = processIfNoneMatchHeader();

	
	
	if($ifMatch && !$ifNoneMatch && !$ifModSin) {  
		if(in_array('*', $ifMatch)) {
			if($rowCount == 0) { 
				pcf($etag, $lastModified);
			} 
		}  elseif(!in_array($etag, $ifMatch)) { 
			pcf($etag, $lastModified);
		}
	} elseif($ifNoneMatch && !$ifMatch && !$ifUnmodSin) {
		if(in_array($etag, $ifNoneMatch)) { 
			if(isset($ifModSin)) {
				if($ifModSin > strtotime($lastModified)) {
					if($_SERVER['REQUEST_METHOD'] === "GET" ||  $_SERVER['REQUEST_METHOD'] === "HEAD") {
						nm($etag, $lastModified);
					} else {
						pcf($etag, $lastModified);
					}
				} 
			} else {
				if($_SERVER['REQUEST_METHOD'] === "GET" ||  $_SERVER['REQUEST_METHOD'] === "HEAD") {
					nm($etag, $lastModified);
				} else {
					pcf($etag, $lastModified);
				}
			}
		} elseif(in_array("*", $ifNoneMatch) && $rowCount != 0) {
			if($_SERVER['REQUEST_METHOD'] === "GET" ||  $_SERVER['REQUEST_METHOD'] === "HEAD") {
				nm($etag, $lastModified);
			} else {
				pcf($etag, $lastModified);
			}
		}		
	} elseif($ifModSin && !$ifMatch && !$ifUnmodSin) { 
		if($ifModSin > strtotime($lastModified)) { 
			nm($etag, $lastModified);
		}
	} elseif($ifUnmodSin && !$ifNoneMatch && !$ifModSin) {
		if($ifUnmodSin < strtotime($lastModified)) {
			pcf($etag, $lastModified);
		}
	}
}

// 304 Not Modified Response Header
function nm($etag, $lastModified) { 
	header('HTTP/1.1 304 Not Modified');
	header('Etag: '.$etag);
	exit;
}

// 412 Prondition Failed Response Header
function pcf($etag, $lastModified) {
	header('HTTP/1.1 412 Precondition Failed');
	header('Etag: '.$etag);
	header('Last-Modified: '.$lastModified);
	exit;	
}

/**
 * Compare two etags using strong comparison
 * according to:
 * https://tools.ietf.org/html/rfc2616#section-13.3.3
 * 
 * @param first etag 		$tag1
 * @param second etag 		$tag2
 * @return boolean * 
 */
function compareEtags($tag1, $tag2) {
	if($tag1 === "*" || $tag2 === "*") {
		return TRUE;
	}
	$arg1 = str_split($tag1);
	$arg2 = str_split($tag2);
	
	$size1 = count($arg1);
	if($size1 != count($arg1)) {
		return FALSE;
	}
	
	for($i = 0; $i < $size1; $i++) {
		if($arg1[$i] !== $arg2[$i]) {
			return FALSE;
		}
	}
	return TRUE;
}



function getDatabaseConnection() {
	/* include(DB_SCRIPT_LOCATION);  FROM DEVELOPMENT */

	try {
		$db = new DBConnection();
		$conn = $db->getConnection();
		return $conn;
	} catch(PDOException $e) {
		echo $e->getMessage();
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function getLastModified($dbconn, $tableName) {
	$stmt = $dbconn->prepare('SELECT DATE_FORMAT(last_modified, "%a, %d %b %Y %T GMT") AS lm FROM table_metadata WHERE table_name=:table');
	$stmt->bindParam(":table", $tableName);
	if($stmt->execute()) {
		$result = $stmt->fetch();
		return $result['lm'];
	} else {
		return null;
	}
}

function authenticateUser($dbconn) {
	$segments = @explode(':', base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));

	if(count($segments) == 2) {
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = $segments;
	}
	
	if (!isset($_SERVER['PHP_AUTH_USER']) || ($_SERVER['PHP_AUTH_USER']) == "")  {
		header('WWW-Authenticate: Basic realm="Modintro"');
		header('HTTP/1.0 401 Unauthorized');
		// echo 'Text to send if user hits Cancel button<br>';
		exit;
	} else {
		$stmt = $dbconn->prepare("SELECT user.userID, user.name, user.type, user.password,
					user_history.registration_date, user_history.last_activity
					FROM user JOIN user_history ON user.userID=user_history.userID_FK
					WHERE email=:email");
		$stmt->bindParam(':email', $_SERVER['PHP_AUTH_USER']);
		$stmt->execute();
		
		if($stmt->rowCount() == 1) {
			$result = $stmt->fetch();
			$stmt->closeCursor();

			if(password_verify( $_SERVER['PHP_AUTH_PW'], $result['password'])) {
				$user =  new User($result['userID'], $result['name'], $_SERVER['PHP_AUTH_USER'],
					$result['type'], $result['registration_date'], $result['last_activity']);
				return $user;
			} else {
				header('HTTP/1.1 401 Unauthorized');
				exit;
			}
		} else { // No record found
			header('HTTP/1.0 401 Unauthorized');
			// header('WWW-Authenticate: Basic realm="Modintro"');
			// echo 'Text to send if user hits Cancel button<br>';
			// echo '{ Error:"Not Found", ErrorCode: 333 }';
			exit;
		}
	}
}

function validateDateTime($datetime) {
	if(preg_match('/^\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}$/', $datetime)) {
		$dt = explode(" ", $datetime);
		$date = explode("-", $dt[0]);
		
		if(checkdate($date[1], $date[2], $date[0])) {
			$time = explode(":", $dt[1]);
			if($time[0] > 23 || $time[1] > 59 || $time[2] > 59) {
				return FALSE;
			}
			return TRUE;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

// For development and debugging only
function output($data) {
	print_r($data);	
}
?>