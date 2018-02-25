<?php


$params = explode("/", $_SERVER['REQUEST_URI']);

/*print_r($params);
echo '<br>';
echo 'Array size = '.count($params);
echo '<br>';
echo $_SERVER['PHP_SELF'].'<br>'; */

// Determine local directory
$temp = explode("/", $_SERVER['PHP_SELF']);
$keys = count($temp) - 1;
$currentDirectory = $temp[$keys - 1];
// echo 'dir = '.$currentDirectory.'<br>';
// print_r($temp);

$HTTPVerb = $_SERVER['REQUEST_METHOD'];

$count = count($params);
$key = array_search($currentDirectory, $params);
$key = $key + 1;
$firstParam = $params[$key];

if($firstParam === "") {
	
	if($HTTPVerb === "DELETE") {
		deleteAllUser();
	} elseif($HTTPVerb === "GET") {
		getAllUser();
	} elseif($HTTPVerb === "HEAD") {
	} elseif($HTTPVerb === "OPTIONS") {
		header("HTTP/1.1 200 OK");
		header("Allow: DELETE, GET, HEAD, POST, PUT");
		exit;
	} elseif($HTTPVerb === "POST") {
		postUser();
	} elseif($HTTPVerb === "PUT") {	
		putAllUser();
	} else {
		header("HTTP/1.1 405 Method Not Allowed");
		header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
		exit;
	}
	
} elseif(is_numeric($firstParam)) {	
	
	$key = $key + 1;
	if($key < $count) {
		$secondParam = $params[$key];
	
		if($secondParam === "connections") {
			$key = $key + 1;
			if($key < $count) {
				$thirdParam = $params[$key];
						
				if($HTTPVerb === "DELETE" && !$thirdParam) {
					deleteAllConnections($firstParam);
				} elseif($HTTPVerb === "POST") {					
					if($thirdParam === "request") {
						requestConnection($firstParam);
					} elseif($thirdParam === "confirm") {
						confirmConnection($firstParam);
					} else {
						echo 'Error *';
					}
				} elseif($HTTPVerb === "OPTIONS") {
					header("HTTP/1.1 200 OK");
					header("Allow: DELETE, POST");
					exit;
				} else {
					echo 'Error';
				}
			} else {
				echo 'Error IIY';
			}
		} elseif($secondParam === "followers") {
			$key = $key + 1;
			if($key < $count) {
				$thirdParam = $params[$key];
			
				if(is_numeric($thirdParam)) {
					if($HTTPVerb === "DELETE") {
						deleteUserFollower($firstParam, $thirdParam);
					} elseif($HTTPVerb === "GET") {
						getUserFollower($firstParam, $thirdParam);
					} elseif($HTTPVerb === "HEAD") {
						header("Last-Modified: 0");
					} elseif($HTTPVerb === "OPTIONS") {
						
					} else {				
						echo 'ERROR';
					}
				} elseif($thirdParam === "") {
					if($HTTPVerb === "DELETE") {
						deleteUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "GET") {
						getUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "HEAD") {
						headUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "OPTIONS") {
						header("HTTP/1.1 200 OK");
						header("Allow: DELETE, GET, HEAD, POST, PUT");
						exit;
					} elseif($HTTPVerb === "POST") {
						patchUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "PUT") {
						putUserAnnouncements($firstParam);
					} else {
						header("HTTP/1.1 405 Method Not Allowed");
						header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
						exit;
					}
				} else {
					echo 'Error iiii';
				}
			} else {
				echo 'ERROR zzzz';
			}
		} elseif($secondParam === "messages") {	
			$key = $key + 1;
			if($key < $count) {
				$thirdParam = $params[$key];
				
				if(is_numeric($thirdParam)) {
					if($HTTPVerb === "DELETE") {
						deleteUserFollower($firstParam, $thirdParam);
					} elseif($HTTPVerb === "GET") {
						getUserAnnouncements($firstParam, $thirdParam);
					} elseif($HTTPVerb === "HEAD") {
						headUserAnnouncements($firstParam, $thirdParam);
					} elseif($HTTPVerb === "OPTIONS") {
						header("HTTP/1.1 200 OK");
						header("Allow: DELETE, GET, HEAD");
						exit;
					} else {
						header("HTTP/1.1 405 Method Not Allowed");
						header("ALLOW:  DELETE, GET, HEAD, OPTIONS");
						exit;
					}
				} elseif($thirdParam === "") {
					if($HTTPVerb === "DELETE") {
						deleteUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "GET") {
						getUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "HEAD") {
						headUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "OPTIONS") {
						header("HTTP/1.1 200 OK");
						header("Allow: DELETE, GET, HEAD, POST, PUT");
						exit;
					} elseif($HTTPVerb === "POST") {
						patchUserAnnouncements($firstParam);
					} elseif($HTTPVerb === "PUT") {
						putUserAnnouncements($firstParam);
					} else {
						header("HTTP/1.1 405 Method Not Allowed");
						header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
						exit;
					}
				} else {
					echo 'ERROR MOTHER FUCKER';
				}
			} else {
				echo 'NO THIRD PARAM';
			}
			/* if($HTTPVerb === "DELETE") {
				deleteUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "GET") {
				getUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "HEAD") {
				headUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "OPTIONS") {
				header("HTTP/1.1 200 OK");
				header("Allow: DELETE, GET, HEAD, POST, PUT");
				exit;
			} elseif($HTTPVerb === "PATCH") {
				patchUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "PUT") {
				putUserAnnouncements($firstParam);
			} else {
				header("HTTP/1.1 405 Method Not Allowed");
				header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
				exit;
			} */
		} elseif($secondParam === "announcements") {
			if($HTTPVerb === "DELETE") {
				deleteUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "GET") {
				getUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "HEAD") {
				headUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "OPTIONS") {
				header("HTTP/1.1 200 OK");
				header("Allow: DELETE, GET, HEAD, POST, PUT");
				exit;
			} elseif($HTTPVerb === "PATCH") {
				patchUserAnnouncements($firstParam);
			} elseif($HTTPVerb === "PUT") {
				putUserAnnouncements($firstParam);
			} else {
				header("HTTP/1.1 405 Method Not Allowed");
				header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
				exit;
			}
		}   else {
			echo 'Error';
		}		
	} else {
		if($HTTPVerb === "DELETE") {
			deleteUser($firstParam);
		} elseif($HTTPVerb === "GET") {
			getUser($firstParam);
		} elseif($HTTPVerb === "HEAD") {
			headUser();
		} elseif($HTTPVerb === "OPTIONS") {
			header("HTTP/1.1 200 OK");
			header("Allow: DELETE, GET, HEAD, PATCH, PUT");
			exit;
		} elseif($HTTPVerb === "PATCH") {
			patchUser($firstParam);
		} elseif($HTTPVerb === "PUT") {
			putUser($firstParam);
		} else {
			header("HTTP/1.1 405 Method Not Allowed");
			header("ALLOW:  DELETE, GET, HEAD, OPTIONS, POST, PUT");
			exit;
		}
	}

} elseif($firstParam === "connections") {
	
	$key = $key + 1;
	if($key < $count) {
		$secondParam = $params[$key];
		
		if($secondParam === "") {
			getConnections();
		} elseif($secondParam === "count") {
			getConnectionCount();
		} else {
			echo 'Error';
		}
		
	} elseif ($key == $count) {
		getConnections();
	} else {
		echo 'Error';
	}
	
} else {
	echo 'Error<br>';
}





function deleteAllUser() {
	echo 'DELETE all users ';
}

function getAllUser() {
	echo 'GET all user';
}

function headAllUser() {
	header("Last-Modified: ");
}

function postUser() {
	echo 'POST user';
}

function putAllUser() {
	echo 'PUT all user';
}




function deleteAllConnections($userId) {
	echo 'DELETE all connections for '.$userId;
}

function requestConnection() {
	echo 'request connection';
}

function confirmConnection() {
	echo 'confirm ';
}



function deleteUserFollowers($userId) {
	echo '';
}

function deleteUserFoller($userId, $thirdParam) {
	echo '';
}

function getUserFollowers($userId) {
	echo '';
}

function getUserFollower($userId, $followerId) {
	echo 'get follower with id='.$followerId.' of user with id='.$userId;
}

// function 



function deleteUserAnnouncements($userId) {
	echo "DELETE user announcements for user ".$userId;
}

function getUserAnnouncements($userId) {
	echo "GET user announcements for user ".$userId;
}

function headUserAnnouncements($firstParam) {
	header("Last-Modified: 0");
}

function patchUserAnnouncements($userId) {
	echo 'PATCH user announcements for user '.$userId;
}

function putUserAnnouncements($userId) {
	echo 'PUT user announcement for user '.$userId;
}


function getConnections() {
	echo 'GET connections';
}

function getConnectionCount() {
	echo 'GET number of connections ';
}



function deleteUser($userId) {
	echo 'DELETE user '.$userId;
}

function getUser($userId) {
	echo 'GET user '.$userId;
}

function headUser() {
	header("Last-Modified: Tue, 15 Nov 1994 12:45:26 GMT");
}

function patchUser($userId) {
	echo 'PATCH user '.$userId;
}

function putUser($userId) {
	echo 'PUT user '.$userId;
}






function postConnectionConfirm() {
	echo 'POST connection confirm';
}

function deleteConnection() {
	echo 'DELETE connection';
}

function getProfile() {
	echo 'GET profile';
}





?>
