<?php
/**
 * @author	Craig Spencer <craigspencer@modintro.com> * 
 * @version	1.0
 */
require '../utilities/tools.php';

// Database connection files
define("DB_CONFIG", "../utilities/config.ini");
define("DB_SCRIPT_LOCATION", "../utilities/DBConnection.php");

// Database table name for this collection
define("DB_TABLE_NAME", "prospective_user");

// Determine local directory
$temp = explode("/", $_SERVER['PHP_SELF']);
$keys = count($temp) - 1;
$currentDirectory = $temp[$keys - 1];

// Isolate the parameters from the base url
$params = explode("/", $_SERVER['REQUEST_URI']);
$count = count($params);
$key = array_search($currentDirectory, $params);
$key = $key + 1;

/**
 * @var string $firstParam	First param from URL
 */
$firstParam = $params[$key];

/**
 * @var string $HTTPVerb	GET, POST, etc.
 */
$HTTPVerb = $_SERVER['REQUEST_METHOD'];

if($firstParam === "") {	// http://<my domain>/prospective-users/
	
	if($HTTPVerb === "DELETE") {
		deleteCollection();
	} elseif($HTTPVerb === "GET") {
		getCollection();
	} elseif($HTTPVerb === "HEAD") { 
		headCollection();
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, POST");
		exit;
	} elseif($HTTPVerb === "POST") {
		postCollection();
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow: GET, POST, PUT, DELETE, OPTIONS, HEAD");
		exit;
	}
	
}  elseif($firstParam === "validate") {	// http://<my domain>/prospective-users/validate
	
	$key = $key + 1;
	if($key < count($params) && is_numeric($params[$key])) {	// http://<my domain>/prospective-users/validate/{email-code}
			$code = $params[$key];
			
			if(!empty($_SERVER['HTTP_FROM'])) {
				$email = $_SERVER['HTTP_FROM'];
			} else {
				error();  // No email in From request header
			}
			
			// Ensure email is valid
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				error();
			}
			
			if($HTTPVerb === "OPTIONS") {
				header("HTTP/1.1 200 OK");
				header("Allow: PATCH");
				exit;
			} elseif($HTTPVerb === "PATCH") {
				validateUser($code, $email);		
			} else {
				header("HTTP/1.1 405 Method Not Allowed");
				header("Allow: OPTIONS, PATCH");
				exit;
			}
	} else {
		error();
	} 
	
} else {
	error();
}



function deleteCollection() {	
	$dbconn = getDatabaseConnection();
	if(authenticateUser($dbconn)) {		
		$stmt = $dbconn->prepare("TRUNCATE TABLE prospective_user");
		if($stmt->execute()) {
			echo '{ "message":"SUCCESS" }';
		} else {
			echo '{ "message":"FAIL" }';
		}
	} 
}

function getCollection() {
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare("SELECT * FROM prospective_user");
	$stmt->execute();	
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$stmt->closeCursor();
	
	$last = getLastModified($dbconn, DB_TABLE_NAME);
	header('Last-Modified: '.$last);
	echo json_encode($results, JSON_PRETTY_PRINT);
}

function headCollection() {
	$dbconn = getDatabaseConnection();
	$last = getLastModified($dbconn, DB_TABLE_NAME);
	header('Last-Modified: '.$last);
	exit;
}

function postCollection() {	
	if(!empty($_POST['email'])) {
		$email = $_POST['email'];
	
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			error();
		}		
		$random_hash = md5(uniqid(rand(), true));
	
		$dbconn = getDatabaseConnection();
		$stmt = $dbconn->prepare("INSERT INTO prospective_user (email_addr, email_verify_code) VALUES(:email, :code)");
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':code', $random_hash);
		if($stmt->execute()) {			
			/**
			 *  @todo add code to send email with code and instructions
			 */
			header('HTTP/1.1 201 Created');
			echo '{ "message":"SUCCESS" }';
		}		
	} else {
		error();
	}
}

function putCollection() {
	//  Method not implemented because an email
	// must be sent for each new entry - multiple entries not allowed
	
	// parse_str(file_get_contents("php://input"), $post_vars);
	// $post_vars = file_get_contents("php://input");
	// $decoded = json_decode($post_vars);
	// print_r($decoded);
}




function validateUser($code, $email) {
	// echo "VALIDATE user ".$code." ".$email;
	
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare("SELECT email_addr FROM prospective_user WHERE email_verify_code=:code");
	$stmt->bindParam(':code', $code);
	$stmt->execute();
	
	if($stmt->rowCount() == 1) {
		$result = $stmt->fetch();
		if($result['email_addr'] === $email) {
			
			$stmt->closeCursor();
			
			$stmt = $dbconn->prepare("UPDATE prospective_user SET email_validated = TRUE WHERE email_verify_code=:code");
			$stmt->bindParam(':code', $code);
			$stmt->execute();			
			
			if($stmt->rowCount() == 1) {
				header("HTTP/1.1 200 OK");
				echo 'Accepted';
				exit;
			}			
		} else {
			error();
		}
	} else {
		error();
	}
}
?>