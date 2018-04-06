<?php
// /messages/index.php

require_once '../../utilities/tools.php';
require_once '../../utilities/appSettings.php';
require_once '../../classes/User.php';

// Check if Content-Type and character set requested by
// client are available
processHeaders();

// Dissect the url and grab http verb
$params = explode("/", $_SERVER['REQUEST_URI']);
$HTTPVerb = $_SERVER['REQUEST_METHOD'];

// Separate URL from query string
$requestURI = explode("?", $_SERVER['REQUEST_URI']);
$requestURI = $requestURI[0];

if(preg_match('/\/messages\/$/', $requestURI)) { 
	/* URL:	/messages/ */
	
	if($HTTPVerb === "DELETE") {
		deleteMessage();
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getMessages($HTTPVerb);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, PUT");
		exit;
	} elseif($HTTPVerb === "POST") { 
		postMessage();
	} elseif($HTTPVerb === "PUT") {
		putMessages();
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
		exit;
	}	
} elseif(preg_match('/^\/messages\/[0-9]+$/', $requestURI)) {
	/* URL:	/messages/{messageID}	*/
	
	$messageId = end($params);
	
	if($HTTPVerb === "DELETE") {
		deleteMessage($messageId);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getMessage($HTTPVerb, $messageId);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, PUT");
		exit;
	} elseif($HTTPVerb === "PUT") {
		putMessage($messageId);
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
		exit;
	}
}

/* URL: /messages/ */
function deleteMessages() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	if($user->getType() === "MASTER") {
		
		$query = "DELETE FROM message";
		$fromFlag = $toFlag = FALSE;
		if(isset($_GET['from'])) {
			$query .= " WHERE userID >= :fromID";
			$fromFlag = TRUE;
		}
		if(isset($_GET['to'])) {
			$toFlag = TRUE;
			if($fromFlag) {
				$query .= " AND userID <= :toID";
			} else {
				$query .= " WHERE userID <= :toID";
			}
		}
		
		$stmt = $dbconn->prepare($query);
		if($fromFlag) {
			$stmt->bindParam(':fromID', $_GET['from']);
		}
		if($toFlag) {
			$stmt->bindParam(':toID', $_GET['to']);
		}
		
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

function getMessages($verb) {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		$userID = $user->getId();
		if($userType === "MASTER" && isset($_GET['userid'])) {
			$userID = $_GET['userid'];
		}
		$query = "SELECT * FROM message WHERE to_userID_FK=:toUserID";
		
		$sortBy = array("title", "from", "date");
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
		
		$stmt = $dbconn->prepare($query);
		$stmt->bindParam(':toUserID', $userID);
		if($stmt->execute()) {
			if($stmt->rowCount() == 0) {
				header('HTTP/1.1 204 No Content');
				exit;
			}		
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
			$messageList = array();
			foreach($results as $row) {
				$message = new Message($row['userID'], $row['name'], $row['email'],
				$row['type'], $row['registration_date'], $row['last_activity']);
				$messageList[] = $user->toArray();
			}
			$userList = Array( "Users" => $userList);
			$output = json_encode($userList);
		
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
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function postMessage() { 
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		if(!empty($_POST)) {
			if(isset($_POST['title']) &&	isset($_POST['body']) && isset($_POST['toUser'])) { 
				$title = $_POST['title'];
				$body = $_POST['body'];
				$toUser = $_POST['toUser'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
					
			if(isset($_POST['type'])) {
				$type = $_POST['type'];
			}
			$stmt = $dbconn->prepare("INSERT INTO message (title, body, to_userID_FK, from_userID_FK, date)
					VALUES(:title, :body, :toUser, :fromUser, NOW())");
			$stmt->bindParam(':title', $title);
			$stmt->bindParam(':body', $body);
			$stmt->bindParam(':toUser', $toUser);
			$stmt->bindParam(':fromUser', $user->getId()); // Message from user who posted it
			if($stmt->execute()) {
				$i = $dbconn->lastInsertId();					
				$location = $_SERVER['REQUEST_URI'].$i;
				
				header('HTTP/1.1 201 Created');
				header('Location: '.$location);			
				
				echo $location;
				exit;
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		echo 'Access denied';
		exit;
	}
}

function putMessages() {
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('Messages', $putVar)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		if($user->getType() === "MASTER") {
			if(isset($putVar['Messages'])) {
				$users = $putVar['Messages'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			$sql = 'INSERT INTO message (title, body, to_UserID_FK, from_userID_FK, date) VALUES ';
			$count = count($users); 
			for($i = 0; $i < $count; $i++) {
				if(isset($messages[$i]['title']) && isset($messages[$i]['body']) &&
						isset($messages[$i]['ToUser']) && isset($messages[$i]['FromUser']) &&
						isset($messages[$i]['Date'])) {
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
				$stmt = $dbconn->prepare("DELETE FROM message");
				$stmt->execute();
				$stmt->closeCursor();
				
				$stmt = $dbconn->prepare($sql);
				$count = count($users);
				$pos = 0;
				foreach($messages as $m) {
					$stmt->bindParam(++$pos, $m['Title']);
					$stmt->bindParam(++$pos, $m['Body']);
					$stmt->bindParam(++$pos, $m['ToUser']);
					$stmt->bindParam(++$pos, $m['FromUser']);
					$stmt->bindParam(++$pos, $m['Date']);
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

/* END URL: /messages/ */


/* URL:	/messages/{messageID}	*/
function deleteMessage($messageId) {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		if($userType === "USER" && $user->getId() != $userId) {
			header('HTTP/1.1 403 Forbidden');
			exit;
		}
		
		// Ensure a user may only delete his own (received) messages
		$stmt = $dbconn->prepare("DELETE FROM message WHERE messageID=:messageID AND to_FK=:toID");
		$stmt->bindParam(':messageID', $messageId);
		$stmt->bindParam(':toID', $user->getId());
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

function getMessage($verb, $messageId) {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {	
		$stmt = $dbconn->prepare("SELECT * FROM message WHERE messageID=:messageID AND to_FK=:userID");
		$stmt->bindParam(':messageID', $messageId);
		$stmt->bindParam(':userID', $user->getId());
		
		if($stmt->execute()) {
			if($stmt->rowCount() == 0) {
				header('HTTP/1.1 204 No Content');
				exit;
			}
			
			if($stmt->rowCount() == 0) {
				header('HTTP/1.1 404 Not Found');
				exit;
			}
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$output = json_encode($results);
			
			$etags = processIfMatchHeader();
			if(!empty($etags)) {
				foreach($etags as $tag) {
					echo $tag."\n";
				}
			}			
		
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
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function putMessage($messageId) {
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists($putVar['Title']) && array_key_exists($putVar['Body'])
			&& array_key_exists($putVar['ToID']) && array_key_exists($putVar['FromID'])
			&& array_key_exists($putVar['Date'])) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
				
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN") {
			$stmt = $dbconn->prepare("SELECT * FROM message WHERE messageID = :messageID");
			$stmt->bindParam(':messageID', $messageId);
			if($stmt->execute()) {
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
				
				if(!empty($results)) {  // Update an existing resource
					$stmt = $dbconn->prepare("UPDATE message SET title=:title, body=:body,
						to_userID_FK=:toID, from_userID_FK, date=:date WHERE messageID=:messageID");				
				} else { // Create a new resource
					$stmt = $dbconn->prepare("INSERT INTO message (messageID, title, body,
						to_userID_FK, from_userID_FK, date)
						VALUES (:messageID, :title, :body, :toID, :fromID, :date)");
				}
			
				$stmt->bindParam(':messageID', $messageId);
				$stmt->bindParam(':title', $putVar['Title']);
				$stmt->bindParam(':body', $putVar['Body']);
				$stmt->bindParam(':to_userID_FK', $putVar['ToID']);
				$stmt->bindParam(':from_userID_FK', $putVar['FromID']);
				$stmt->bindParam(':date', $putVar['Date']);
					
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
					exit;
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					exit;
				}
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
/* END URL:	/messages/{messageID}	*/
?>