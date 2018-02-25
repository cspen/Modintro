<?php
/**
 * Validate login credentials via HTTP basic authentication.
 */
class ValidateUser {
	
	public static function validate() {
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

		if (($_SERVER['PHP_AUTH_USER']) == "") { // No Login sent
			header('WWW-Authenticate: Basic realm="Modintro"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Text to send if user hits Cancel button<br>' + showHeaderTable();
			exit;
		} else {
			// Validate credentials against database record
			if((@include 'DBConnect.php') === false) {
				// Handle the error
			}
		}
	}
} 

?>