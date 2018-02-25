<?php

$params = explode("/", $_SERVER['REQUEST_URI']);
// print_r($params);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
	if(!empty($_POST['code'])) {
		include '../utilities/DBConnection.php';
		$db = new DBConnection('../utilities/config.ini');
		$dbcon = $db->getConnection();
		
		// Insert email and code into database
		$stmt = $dbcon->prepare("SELECT email_addr FROM new_user WHERE email_verify_code=:code");
		$stmt->bindParam(':code', $_POST['code']);
		$stmt->execute();
		
		if($stmt->rowCount() == 1) {
			$result = $stmt->fetch();
			if($result['email_addr']) {
				echo '{ Email: '.$result['email_addr'].', Code: '.$_POST['code'].'}';
			}
		} else { // No record found
			header('HTTP/1.1 204 No Content');
			echo '{ Error:"Not Found", ErrorCode: 333 }';
			exit;
		}

	} else { 
		header('HTTP/1.1 400 Bad Request');
		echo 'NO POST VARIABLES SENT';
		exit;
	}
	
} elseif ($_SERVER['REQUEST_METHOD'] === "GET") {
	
	echo $_SERVER['REQUEST_URI'];
	
} elseif ($_SERVER['REQUEST_METHOD'] === "PUT") {
	
	header("HTTP/1.1 405 Method Not Allowed");
	header("Allow: GET, POST, HEAD");
	exit;
	
} elseif ($_SERVER['REQUEST_METHOD'] === "DELETE") {
	
	// Administrators only
	
} elseif ($_SERVER['REQUEST_METHOD'] === "HEAD") {
	
	// Send standard 200 OK response header - default
	
} elseif ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
	
	header("HTTP/1.1 200 OK");
	header("Allow: GET, POST, HEAD");
	exit;
	
} else {
	
	header("HTTP/1.1 405 Method Not Allowed");
	header("ALLOW: GET, POST, HEAD");
	exit;
	
}
?>