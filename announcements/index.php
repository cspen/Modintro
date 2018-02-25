<?php
// /announcements/index.php

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


if(preg_match('/^\/workspace\/opal\/announcements\/$/', $requestURI)) {
	/* URL:	/announcements/ */
	
	if($HTTPVerb === "DELETE") {
		deleteAnnouncement();
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getAnnouncement($HTTPVerb);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, POST, PUT");
		exit;
	} elseif($HTTPverb === "POST") {
		postAnnouncement();
	} elseif($HTTPVerb === "PUT") {
		putAnnouncement();
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
		exit;
	}
} elseif(preg_match('/^\/workspace\/opal\/announcements\/[0-9]+$/', $requestURI)) {
	/* URL:	/announcements/{announcementID}	*/
	
	$announcementId = end($params);
	
	if($HTTPVerb === "DELETE") {
		deleteAnnouncement($announcementId);
	} elseif($HTTPVerb === "GET" || $HTTPVerb === "HEAD") {
		getAnnouncement($HTTPVerb, $announcementId);
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, PUT");
		exit;
	} elseif($HTTPVerb === "PUT") {
		putAnnouncement($announcementId);
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("Allow:  DELETE, GET, HEAD, OPTIONS, PUT");
		exit;
	}
}

/* URL: /announcements/ */
function deleteAccouncements() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	if($user->getType() === "MASTER") {
		$query = "DELETE FROM announcement";
		$fromFlag = $toFlag = FALSE;
		if(isset($_GET['from'])) {
			$query .= " WHERE announcementID >= :fromID";
			$fromFlag = TRUE;
		}
		if(isset($_GET['to'])) {
			$toFlag = TRUE;
			if($fromFlag) {
				$query .= " AND announcementID <= :toID";
			} else {
				$query .= " WHERE announcementID <= :toID";
			}
		}
		
		$stmt = $dbconn->prepare($query);
		if($fromFlag) {
			$stmt->bindParam(':fromID', $_GET['from']);
		}
		if($toFlag) {
			$stmt->bindParam(':toID', $_GET['to']);
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

function getAnnouncements($verb) {	
	$query = "SELECT * FROM announcement WHERE deleted=FALSE";
	
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
	
	$sortBy = array("date", "headline", "type");
	if(isset($_GET['sort'])) {
		if(in_array($_GET['sort'], $sortBy)) {
			$query .= " ORDER BY ".$_GET['sort'];
		} elseif(isset($_GET['sort']) && $_GET['sort'] == "userid") {
			$query .= " ORDER BY userID_FK";		
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
		$aList = array();
		foreach($results as $row) {
			$announcement = new Announcement($row['announcementID'], $row['userID_FK'], $row['date_posted'],
					$row['headline'], $row['body'], $row['previous'], $row['allow_comments'],
					$row['deleted'], $row['etag'], $row['last_modified']);
			$aList[] = $announcement->toArray();
		}
		$aList = Array( "Announcements" => $aList);
		$output = json_encode($aList);
		
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

function postAnnouncement() {
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		
		if(!empty($_POST)) {
			$mflag = FALSE;
			if(($userType === "MASTER" || $userType === "ADMIN") && isset($_POST['userid_fk'])) {
				$userID_FK = $_POST['userid_fk'];
				$mflag = TRUE;
			}
			if(isset($_POST['date_posted']) && $_POST['headline'] && isset($_POST['body']) && isset($_POST[''])) {
				$headline = trim($_POST['headline']);
				$body = $_POST['body'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}

			$stmt = $dbconn->prepare("INSERT INTO announcement
				(userID_FK, date_posted, headline, body, previous, allow_comments, deleted)
				VALUES(:userID, NOW(), :headline, :body, :previous, :allowComments)");	
				
			if($mflag) {
				$stmt->bindParam(':userID_FK', $userID_FK);
			} else {
				$stmt->bindParam(':userID', $user->getId());
			}
			$stmt->bindParam(':headline', $headline);
			$stmt->bindParam(':body', $body);
			$stmt->bindParam(':previous', $previos);
			$stmt->bindParam(':allowCommnets', $allowComments);
			
			if($stmt->execute()) {
				$i = $dbconn->lastInsertId();
				$location = $_SERVER['REQUEST_URI'].$i;
				header('Content-Location: '.$location);
				echo $location;
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
	} else {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

function putAnnouncements() {
	if($input = json_decode(file_get_contents("php://input"), true)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN") {
			
			if(isset($input['Announcements'])) {
				$announcements = $input['Announcements'];
			} else {
				header('HTTP/1.1 400 Bad Request');
				exit;
			}
			
			$sql = 'INSERT INTO announcement (userID_FK, date_posted, headline, body,
					 previous, allow_comments, deleted) VALUES ';
			$count = count($announcements);
			for($i = 0; $i < $count; $i++) {
				if(isset($announcement[$i]['UserID']) && isset($announcements[$i]['DatePosted'])
						&& isset($announcements[$i]['Headline']) && isset($announcements[$i]['Body'])
						&& isset($announcements[$i]['Previous']) && isset($announcements[$i]['AllowComments'])
						&& isset($announcements[$i]['Deleted'])) {
							$sql .= '(?, ?, ?, ?, ?, ?, ?)';
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
				$stmt = $dbconn->prepare("DELETE FROM announcement WHERE userID_FK=:userID");
				$stmt->bindParam(':userID', $userId);
				$stmt->execute();
				$stmt->closeCursor();
				
				$stmt = $dbconn->prepare($sql);
				$count = count($announcements);
				$pos = 0;
				foreach($announcements as $a) {
					$stmt->bindParam(++$pos, $a['UserID']);
					$stmt->bindParam(++$pos, $a['DatePosted']);
					$stmt->bindParam(++$pos, $a['Headline']);
					$stmt->bindParam(++$pos, $a['Body']);
					$stmt->bindParam(++$pos, $a['Previous']);
					$stmt->bindParam(++$pos, $a['AllowComments']);
					$stmt->bindParam(++$pos, $a['Deleted']);
				}
				
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					exit;
				}
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
/* END URL: /announcements/ */


/* URL:	/announcements/{announcementID}	*/
function deleteAnnouncement($announcementId) {
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		header('HTTP/1.1 412 Precondition Failed');
		exit;
	}
	$dbconn = getDatabaseConnection();
	$user = authenticateUser($dbconn);
	
	$userType = $user->getType();
	if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
		// First check record against headers
		$stmt = $dbconn->prepare("SELECT * FROM announcement WHERE userID_FK=:userID AND announcementID=:announcementID");
		
		if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
			$stmt->bindParam(':userID', $_GET['userid']);
		} else {
			$stmt->bindParam(':userID', $user->getId());
		}
		$stmt->bindParam(':announcementID', $announcementId);
		
		if($stmt->execute()) {
			$rowCount = $stmt->rowCount();
			if($rowCount == 1) {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				processConditionalHeaders($result['etag'], $rowCount, $result['last_modified']);
				
				// Delete the resource
				$stmt = $dbconn->prepare("DELETE FROM announcement WHERE userID_FK=:userID AND anouncementID=:announcementID");
				
				if(($userType === "MASTER" || $userType === "ADMIN") && isset($_GET['userid'])) {
					$stmt->bindParam(':userID', $_GET['userid']);
				} else {
					$stmt->bindParam(':userID', $user->getId());
				}
				$stmt->bindParam(':announcementID', $announcementId);
				
				if($stmt->execute()) {
					header('HTTP/1.1 204 No Content');
					exit;
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					exit;
				}				
			} else {
				header('HTTP/1.1 204 No Content');
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
}

function getAnnouncement($verb, $announcementId) {	
	$query = "SELECT * FROM announcement WHERE announcementID=:announcementID";
	
	$dbconn = getDatabaseConnection();	
	$stmt = $dbconn->prepare();
	$stmt->bindParam(':announcementID', $announcementId);
	if($stmt->execute()) {
		$rowCount = $stmt->rowCount();		
		
		$ifModSin = processIfModifiedHeaders();
		$ifUnmodSin = processIfUnmodifiedSinceHeader($resTime);
		$ifMatch = processIfMatchHeader();
		$ifNoneMatch = processIfNoneMatchHeader();
		
		if($ifMatch && !$ifNoneMatch && !$ifModSin) {
			if(!in_array($result['etag'], $ifMatch) || (in_array("*", $ifMatch) && $rowCount == 0)) {
				header('HTTP/1.1 412 Precondition Failed');
				exit;
			}
		} elseif($ifNoneMatch && !$ifMatch && !$ifUnmodSin) {
			if(in_array($result['etag'], $ifNoneMatch) || in_array("*", $ifNoneMatch)) {
				if($ifModSin) {
					if($ifModSin == strtotime($result['last_modified'])) {
						header('HTTP/1.1 304 Not Modified');
						exit;					
					}
				} else {
					header('HTTP/1.1 304 Not Modified');
					header('Etag: '.$result['etag']);
					header('Last-Modified: '.$result['last_modified']);
					exit;
				}
			}
		} elseif($ifModSin && !$ifMatch && !$ifUnmodSin) {
			if($ifModSin == strtotime($result['last_modified'])) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		} elseif($ifUnmodSin && !$ifNoneMatch && !$ifModSin) {
			if($ifUnModSin != strtotime($result['last_modified'])) {
				header('HTTP/1.1 412 Precondition Failed');
				exit;
			}
		}
		
		json_encode($result);
			
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		header('Content-Length: '.strlen($output));
		header('Etag: '.$result['etag']);
		header('Last-Modified: '.$result['last_modified']);

		if($verb === "GET") {
			echo $output;
		}
		exit;
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
}

function putAnnouncement($announcementId) {
	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		header('HTTP/1.1 412 Precondition Failed');
		exit;
	}
	
	$putVar = json_decode(file_get_contents("php://input"), true);
	if(isset($putVar) && array_key_exists('Headline', $putVar) && array_key_exists('Body', $putVar)) {
		$dbconn = getDatabaseConnection();
		$user = authenticateUser($dbconn);
		
		$userType = $user->getType();
		if($userType === "MASTER" || $userType === "ADMIN" || $userType === "USER") {
			
			$stmt = $dbconn->prepare("SELECT * FROM announcement WHERE announcementID = :announcementID");
			$stmt->bindParam(':announcementID', $announcementId);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$rowCount = $stmt->rowCount();
			$stmt->closeCursor();			
			
			if($rowCount == 1) { // Update (replace) existing resource
				
				processConditionalHeaders($result['etag'], $stmt->rowCount(), $result['last_modified']);
				
				$stmt = $dbconn->prepare("UPDATE announcement SET date_posted=NOW(), headline=:headline, body=:body
								WHERE announcementID=:announcementID");
				$stmt->bindParam(':headline', $putVar['Headline']);
				$stmt->bindParam(':body', $putVar['Body']);
				$stmt->bindParam(':announcementID', $announcementId);
				
			} else { // Create a new resource
				processConditionalHeaders(null, 0, null);
				
				$stmt = $dbconn->prepare("INSERT INTO announcement (announcementID, userID_FK, date_posted, headline, body)
								VALUES(:announcementID, :userID, NOW(), :headline, :body)");
				$stmt->bindParam(':announcementID', $announcementId);
				$stmt->bindParam(':userID', $user->getId());
				$stmt->bindParam(':headline', $putVar['Headline']);
				$stmt->bindParam(':body', $putVar['Body']);
			}
			
			if($stmt->execute()) {
				header('HTTP/1.1 204 No Content');
				exit;
			} else {
				header('HTTP/1.1 504 Internal Server Error');
				exit;
			}
		}
	} else {
		header('HTTP/1.1 400 Bad Request');
		exit;
	}
}
/* END URL:	/announcements/{announcementID}	*/
?>