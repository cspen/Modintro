<?php

class User {
	
	function __construct() {
		$args = func_get_args();
		$num = func_num_args();
		if (method_exists($this,$f='__construct'.$num)) {
			call_user_func_array(array($this,$f),$args);
		} 
	}
	
	function __construct3($name, $email, $type) { 
		$this->name = $name;
		$this->email = $email;
		$this->type = $type;
	}

	function __construct6($id, $name, $email, $type, $regDate, $lastActivity) {
		$this->id = $id;
		$this->name = $name;
		$this->email = $email;
		$this->type = $type;
		$this->regDate = $regDate;
		$this->lastActivity = $lastActivity;
	}
	
	function toJSON() {		
		$user = $this->toArray();
		$out = array("User"=>$user);		
		return json_encode($out);
	}
	
	function toArray() {
		$user = array(
				"Id"=>$this->id,
				"Name"=>$this->name,
				"Email"=>$this->email,
				"Type"=>$this->type,
				"Registration Date"=>$this->regDate,
				"Last Activity"=>$this->lastActivity
		);
		return $user;
	}
	
// Getters
	function getId() {
		return $this->id;
	}
	
	function getName() {
		return $this->name;
	}
	
	function getEmail() {
		return $this->email;
	}
	
	function getType() {
		return $this->type;
	}
	
	function getRegDate() {
		return $this->regDate;
	}
	
	function getLastActivity() {
		return $this->lastActivity;
	}
	
// Setters
	function setId($newId) {
		$this->id = $newId;
	}
	
	function setName($newName) {
		$this->name = $newName;
	}
	
	function setEmail($newEmail) {
		$this->email = $newEmail;
	}
	
	function setType($newType) {
		$this->type = $newType;
	}
	
	function setLastActivity($newAction) {
		$this->lastActivity = $newAction;
	}
	
// Components
	// From user table
	private $id;
	private $name;
	private $email;
	private $type;
	
	// From user_history table
	private $regDate;
	private $lastActivity;
}