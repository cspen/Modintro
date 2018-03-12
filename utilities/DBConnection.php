<?php
/**
 * Connect to a MySQL database with PDO using credentials stored in a config.ini file.
 ^
 ^ NOTE: This file needs to be stored on the server above the root level for the
 * web application to avoid public exposure of the db credentials.
 */

class DBConnection {
	
	function __construct($dbConfigFile) {
		if(!isset($this->connection)) {			
			try {
				$this->connection = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo $e->getMessage();
			}
		}		
	}
	
	// For queries without parameters
	function db_query($query) {
		$stmt = $this->connection->query($query);
		return $stmt;
	}
	
	// For queries with parameters
	function getConnection() {
		return $this->connection;
	}

	// DB credentials
	private 	$username = 'dbo727481365';
	private		$password = 'J1llyB3an!';
	private		$dbname = 'db727481365';
	private		$servername = 'db727481365.db.1and1.com';
	
	// DB connection
	private		$connection;

}
?>