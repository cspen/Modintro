<?php

class Message {
	
	function __construct() {
		$args = func_get_args();
		$num = func_num_args();
		if (method_exists($this,$f='__construct'.$num)) {
			call_user_func_array(array($this,$f),$args);
		} 
	}
	
	function __construct4($title, $body, $toUser, $fromUser) {
		$this->title = $title;
		$this->body = $body;
		$this->toUser = $toUser;
		$this->fromUser = $fromUser;		
	}
	
	function __construct9($messageId, $title, $body, $toUser, $fromUser, 
			$date, $accepted, $etag, $lastModified) {
		$this->id = $messageId;
		$this->title = $title;
		$this->body = $body;
		$this->toUser = $toUser;
		$this->fromUser = $fromUser;
		$this->date = $date;
		$this->accepted = $accepted;
		$this->etag = $etag;
		$this->lastModified = $lastModified;
	}
	
	function toJSON() {
		$message = $this->toArray();
		$out = array("Message"=>$message);
		return json_encode($out);
	}
	
	function toArray() {
		$user = array(
				"Id"=>$this->id,
				"Title"=>$this->title,
				"Body"=>$this->email,
				"To User"=>$this->type,
				"From User"=>$this->regDate,
				"Accepted"=>$this->accepted,
				"Date"=>$this->date,
				"Etag"=>$this->etag,
				"Last Modified"=>$this->lastModified
		);
		return $user;
	}
	
// Getters
	function getId() {
		return $this->id;
	}
	
	function getTitle() {
		return $this->title;
	}
	
	function getBody() {
		return $this->body;
	}
	
	function getToUser() {
		return $this->toUser;
	}
	
	function getFromUser() {
		return $this->fromUser;
	}
	
	function getDate() {
		return $this->date;
	}
	
	function accepted() {
		return $this->accepted;
	}
	
	function getEtag() {
		return $this->etag;
	}
	
	function lastModified() {
		return $this->lastModified;
	}
// Setters
	function setId($id) {
		$this->id = $id;
	}
	
	function setTitle($title) {
		$this->title = $title;
	}
	
	function setBody($body) {
		$this->body = $body;
	}
	
	function setToUser($toUser) {
		$this->toUser = $toUser;
	}
	
	function setFromUser($fromUser) {
		$this->fromUser = $fromUser;
	}
	
	function setDate($date) {
		$this->date = $date;
	}
	
	function setAccepted($accepted) {
		$this->accepted = $accepted;
	}
	
// Components
	private $id;
	private $title;
	private $body;
	private $toUser;
	private $fromUser;
	private $date;
	private $accepted;
	private $etag;
	private $lastModified;	
}
?>