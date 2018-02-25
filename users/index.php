<?php
// /users/index.php

require_once '../utilities/tools.php';
require_once '../utilities/appSettings.php';
require_once '../classes/User.php';

// Check if Content-Type  and character set requested by
// client are available
processHeaders();

// Dissect the url and grab http verb
$params = explode("/", $_SERVER['REQUEST_URI']);
$HTTPVerb = $_SERVER['REQUEST_METHOD'];

// Separate URL from query string
$requestURI = explode("?", $_SERVER['REQUEST_URI']);
$requestURI = $requestURI[0];

// Database connection files
define("DB_CONFIG", "../utilities/config.ini");
define("DB_SCRIPT_LOCATION", "../utilities/DBConnection.php");


if(preg_match('/^\/workspace\/opal\/users\/$/', $requestURI)) {
/* URL: /workspace/opal/users/	*/
	
	if($HTTPVerb === "DELETE") {
		deleteUsers();
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getUsers($HTTPVerb);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, POST, PUT");
		exit;
	} elseif($HTTPVerb === "POST") {
		postUsers();
	} elseif($HTTPVerb === "PUT") {
		putUsers();
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
		exit;
	}
	
} elseif(preg_match('/^\/workspace\/opal\/users\/[0-9]+$/', $requestURI)) {
/* URL: /workspace/opal/users/{userID}	*/
	
	$id = end($params);
	
	if($HTTPVerb === "DELETE") {
		deleteUser($id);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getUser($HTTPVerb, $id);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, PUT");
		exit;
	} elseif($HTTPVerb === "PUT") {
		putUser($id);
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
		exit;
	}
	
} elseif(preg_match('/^\/workspace\/opal\/users\/[0-9]+\/announcements\/$/', $requestURI)) {
/* URL: /workspace/opal/users/{userID}/announcements/	*/
	
	end($params);
	prev($params);
	$id = prev($params);
	
	if($HTTPVerb === "DELETE") {
		deleteAnnouncements($id);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getAnnouncements($HTTPVerb, $id);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD");
		exit;
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS");
		exit;
	}
} elseif(preg_match('/^\/workspace\/opal\/users\/[0-9]+\/followers\/$/', $requestURI)) { 
/* URL: /workspace/opal/users/{userID}/followers/	*/
	
	end($params);
	prev($params);
	$id = prev($params); echo 'F USERID = '.$id."\n";
	
	if($HTTPVerb === "DELETE") {
		deleteFollowers($id);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getFollowers($HTTPVerb, $id);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD");
		exit;
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS");
		exit;
	}	
} elseif(preg_match('/^\/workspace\/opal\/users\/[0-9]+\/messages\/$/', $requestURI)) {
/* URL:	/workspace/opal/users/{userID}/messages/	*/
	
	end($params);
	prev($params);
	$id = prev($params);
	
	if($HTTPVerb === "DELETE") {
		deleteMessages($id);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getMessages($HTTPVerb, $id);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD");
		exit;
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS");
		exit;
	}
} else {
	header('HTTP/1.1 400 Bad Request');
	exit;
}

/*************************************************************************
 * Functions
 */

/* URL: /workspace/opal/users/	*/
function deleteUsers() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	if($user->getType() === "MASTER") {
		
		$query = "DELETE FROM user";
		$fromFlag = $toFlag = FALSE;		
		if(isset($_GET['from'])) {
			$query .= " WHERE userID >= :fromUserID";
			$fromFlag = TRUE;
		}
		if(isset($_GET['to'])) {
			$toFlag = TRUE;
			if($fromFlag) {
				$query .= " AND userID <= :toUserID";
			} else {
				$query .= " WHERE userID <= :toUserID";
			}
		}		
		
		$stmt = $dbconn->prepare($query);
		if($fromFlag) {
			$stmt->bindParam(':fromUserID', $_GET['from']);
		}
		if($toFlag) {
			$stmt->bindParam(':toUserID', $_GET['to']);
		}
		echo $query."\n\n";
		if($stmt->execute()) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}		
	} else {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
}

/**
 * 
 * @param string $verb Either HTTP/1.1 GET or HEAD.
 * @return void
 */
function getUsers($verb) {	
	
	$query = "SELECT user.userID, user.name, user.email, user.type,
		user_history.registration_date, user_history.last_activity
		FROM user LEFT JOIN user_history ON user_history.userID_FK=user.userID";	
	
	// Process url parameters
	if(isset($_GET['page']) && isset($_GET['pagesize'])) {
		if($_GET['page'] >= 0 && $_GET['pagesize'] >= 0) {
			$page = ($_GET['page'] - 1) * $_GET['pagesize'];
			$query .= " limit ".$page.", ".$_GET['pagesize'];
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
	}
	
	$sortBy = array("userid", "name", "email", "type");
	if(isset($_GET['sort'])) {
		if(in_array($_GET['sort'], $sortBy)) {
			$query .=" ORDER BY ".$_GET['sort'];			 
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
		
		// Sort order - asc default
		if(isset($_GET['order'])) {
			$order = $_GET['order'];
			if($order === "desc") {
				$query .= " DESC";
				// ascending is default
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
		}
	}
	
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare($query);
	if($stmt->execute()) {
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
		$userList = array();
		foreach($results as $row) {
			$user = new User($row['userID'], $row['name'], $row['email'],
					$row['type'], $row['registration_date'], $row['last_activity']);
			$userList[] = $user->toArray();
		}
		$userList = Array( "Users" => $userList);
		$output = json_encode($userList, JSON_PRETTY_PRINT);
	
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));		
	
		if($verb === "GET") {
			echo $output;
		} 
		exit;
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function postUsers() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN") {
		if(!empty($_POST)) {
			if(isset($_POST['name']) &&
					isset($_POST['password']) &&
					isset($_POST['email'])) {
				$name = $_POST['name'];
				$password = $_POST['password'];
				$email = $_POST['email'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			if(isset($_POST['type'])) {
				$type = $_POST['type'];
			}
			$stmt = $dbconn->prepare("INSERT INTO user (name, password, email, type)
					VALUES(:name, :password, :email, :type)");
			$stmt->bindParam(':name', $name);
			$stmt->bindParam(':password', $password);
			$stmt->bindParam(':email', $email);
			$stmt->bindParam(':type', $type);
			$stmt->execute();
			$i = $dbconn->lastInsertId();
			
			$location = $_SERVER['REQUEST_URI'].$i;
			header('HTTP/1.1 201 Created');
			header('Location: '.$location);
			
			echo $location;
		}			
	} else {
		header('HTTP/1.1 403 Forbidden');
		echo 'Access denied';
		exit;
	}
}

function putUsers() {
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('Users', $putVar)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		if($user->getType() === "MASTER") {	
			if(isset($putVar['Users'])) {
				$users = $putVar['Users'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
							
			$sql = 'INSERT INTO user (name, password, email, type) VALUES ';
			$count = count($users); echo 'Count: '.$count;
			for($i = 0; $i < $count; $i++) {
				if(isset($users[$i]['Name']) && isset($users[$i]['Password']) &&
						isset($users[$i]['Email']) && isset($users[$i]['Type'])) {
					$sql .= '(?, ?, ?, ?)';
					if($i < ($count - 1)) {
						$sql .= ', ';
					}
				} else {
					header('HTTP/1.1 400 Bad Request');
					exit;
				}
			}
			
			try {
				// Should use transaction but can't with MyISAM
				$stmt = $dbconn->prepare("DELETE FROM user");
				$stmt->execute();
				$stmt->closeCursor();			
			
				$stmt = $dbconn->prepare($sql);
				$count = count($users);
				$pos = 0;
				foreach($users as $user) {
					$stmt->bindParam(++$pos, $user['Name']);
					$stmt->bindParam(++$pos, $user['Password']);
					$stmt->bindParam(++$pos, $user['Email']);
					$stmt->bindParam(++$pos, $user['Type']);
				}
				$stmt->execute();
			} catch(PDOException $e) {
				echo $e->getMessage();
			}
		} else {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
	} else {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}	
}
/* END URL: /workspace/opal/users/	*/


// URL: /workspace/opal/users/{userID}
function deleteUser($userId) {	
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN") {
		$stmt = $dbconn->prepare("DELETE FROM user WHERE userID = :userID");
		$stmt->bindParam(':userID', $userId);
				
		if($stmt->execute()) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 404 Not Found');
			exit;
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function getUser($verb, $userId) {	
	$dbconn = getDatabaseConnection();	
	$stmt = $dbconn->prepare(
			"SELECT user.userID, user.name, user.email, user.type,
				 user_history.registration_date, user_history.last_activity
			 FROM user LEFT JOIN user_history ON user_history.userID_FK=user.userID
			 WHERE user.userID = :userID");
	$stmt->bindParam(':userID', $userId);
	if($stmt->execute()) {
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 404 Not Found');
			exit;
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$output = json_encode($results[0]);
		
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));
		
		if($verb === "GET") {
			echo $output;
		}
		exit;			
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function putUser($userId) {
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('UserID', $putVar) && array_key_exists($putVar['Name'])
			&& array_key_exists($putVar['Password']) && array_key_exists($putVar['Email'])
			&& array_key_exists($putVar['Type'])) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
	
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN") {
			$stmt = $dbconn->prepare("SELECT * FROM user WHERE userID = :userID");
			$stmt->bindParam(':userID', $userId);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			if(!empty($results)) { // Update an existing resource
				$stmt = $dbconn->prepare("UPDATE user SET name=:name, password=:password,
								 email=:email, type=:type WHERE userID=:userID");				
			} else { // Create a new resource
				$stmt = $dbconn->prepare("INSERT INTO user (userID, name, password, email, type)
								 VALUES (:userID, :name, :password, :email, :type)");
			}
			
			$stmt->bindParam(':userID', $putVar['UserID']);
			$stmt->bindParam(':name', $putVar['Name']);
			$stmt->bindParam(':password', $putVar['Password']);
			$stmt->bindParam(':email', $putVar['Email']);
			$stmt->bindParam(':type', $putVar['Type']);			
			
			if($stmt->execute()) {
				header('HTTP/1.1 204 No Content');
				exit;
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		} else {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
	} else {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
}
/* END URL: /workspace/opal/users/{userID}	*/


/* URL: /workspace/opal/users/{userID}/announcements/	*/
function deleteAnnouncements($userId) {
	
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType=== "USER") {
		// Ensure users can only delete their own announcements
		if($level === "USER") {
			if($user->getId() != $userId) {
				header('HTTP/1.1 403 Forbidden');
				exit;
			}			
		}
		
		$stmt = $dbconn->prepare("DELETE FROM announcement WHERE userID_FK=:userID");
		$stmt->bindParam(':userID', $userId);
		$stmt->execute();
		
		if($stmt->rowCount() > 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 404 Not Found');
			exit;
		}		
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
		
	echo 'DELETE user announcements for user '.$userId;
}

function getAnnouncements($verb, $userId) { 
	
	$query = "SELECT * FROM announcement WHERE userID_FK=:userID";
	
	// Process url parameters
	if(isset($_GET['page']) && isset($_GET['pagesize'])) {
		if($_GET['page'] >= 0 && $_GET['pagesize'] >= 0) {
			$page = ($_GET['page'] - 1) * $_GET['pagesize'];
			$query .= " limit ".$page.", ".$_GET['pagesize'];
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
	}
	
	$sortBy = array("announcementid", "date_posted", "headline", "last_modified");
	if(isset($_GET['sort'])) {
		if(in_array($_GET['sort'], $sortBy)) {
			$query .=" ORDER BY ".$_GET['sort'];
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
		
		// Sort order - asc default
		if(isset($_GET['order'])) {
			$order = $_GET['order'];
			if($order === "desc") {
				$query .= " DESC";
				// ascending is default
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
		}
	}
	
	$dbconn = getDatabaseConnection();	
	$stmt = $dbconn->prepare($query);
	$stmt->bindParam(':userID', $userId);
	if($stmt->execute()) {
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		} 
	
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$output = json_encode($results);
		
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));
		
		if($verb === "GET") {
			echo $output;
		}
		exit;
	} else {
		header('HTTP/1.1 ');
		exit;
	}
}
/* END URL: /workspace/opal/users/{userID}/announcements/	*/




/* URL: /workspace/opal/users/{userID}/followers/	*/
function deleteFollowers($userId) {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		if($userType === "USER" && $user->getId() != $userId) {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		
		$stmt = $dbconn->prepare("DELETE FROM message WHERE messageID=:messageID AND to_FK=:userID");
		$stmt->bindParam(':messageID', $messageId);
		$stmt->bindParam(':userID', $userId);
		if($stmt->execute()) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function getFollowers($verb, $userId) {
	echo 'GET followers of user with id='.$userId;
}
/* END URL: /workspace/opal/users/{userID}/followers/	*/




/* URL:	/workspace/opal/users/{userID}/messages/	*/
function deleteMessages($userId) {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		if($userType === "USER" && $user->getId() != $userId) {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		
		$stmt = $dbconn->prepare("DELETE FROM message WHERE AND to_FK=:userID");
		$stmt->bindParam(':userID', $userId);
		if($stmt->execute()) {
			header('HTTP/1.1 204 No Content');
			exit;
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function getMessages($verb, $userId) {
	
	$query = "SELECT * FROM message WHERE to_userID_FK=:userID";
	
	// Process url parameters
	if(isset($_GET['page']) && isset($_GET['pagesize'])) {
		if($_GET['page'] >= 0 && $_GET['pagesize'] >= 0) {
			$page = ($_GET['page'] - 1) * $_GET['pagesize'];
			$query .= " limit ".$page.", ".$_GET['pagesize'];
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
	}
	
	$sortBy = array("title", "date", "from");
	if(isset($_GET['sort'])) {
		if(in_array($_GET['sort'], $sortBy)) {
			if($_GET['sort'] === "from") {
				$query .= " ORDER BY from_userID_FK";
			} else {
				$query .=" ORDER BY ".$_GET['sort'];
			}
		} else {
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
		
		// Sort order - asc default
		if(isset($_GET['order'])) {
			$order = $_GET['order'];
			if($order === "desc") {
				$query .= " DESC";
				// ascending is default
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
		}
	}
	
	$dbconn = getDatabaseConnection();
	$stmt = $dbconn->prepare($query);
	$stmt->bindParam(':userID', $userId);
	if($stmt->execute()) {		
		if($stmt->rowCount() == 0) {
			header('HTTP/1.1 204 No Content');
			exit;
		}
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$userList = array();
		foreach($results as $row) {
			$user = new User($row['userID'], $row['name'], $row['email'],
					$row['type'], $row['registration_date'], $row['last_activity']);
			$userList[] = $user->toArray();
		}
		$userList = Array( "Messages" => $userList);
		$output = json_encode($userList, JSON_PRETTY_PRINT); // print_r($userList, TRUE);
		
		// Set headers
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));		
		
		if($verb === "GET") {
			echo $output;
		} 
		exit;
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}
/* END URL:	/workspace/opal/users/{userID}/messages/	*/
?>