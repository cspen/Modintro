<?php

class Announcement {
	
	function __construct() {
		$args = func_get_args();
		$num = func_num_args();
		if (method_exists($this,$f='__construct'.$num)) {
			call_user_func_array(array($this,$f),$args);
		}
	}
	
	function __construct2($userID, $body) {
		$this->userID = $userID;
		$this->body = $body;
	}
	
	function __construct3($userID, $headline, $body) {
		$this->userID = $userID;
		$this->headline = $headline;
		$this->body = $body;
	}
	
	function __construct10($announcementID, $userID, $datePosted, $headline,
			$body, $previous, $allowComments, $deleted, $etag, $lastModified) {
		$this->announcementID = $announcementID;
		$this->userID = $userID;
		$this->datePosted = $datePosted;
		$this->headline = $headline;
		$this->body = $body;
		$this->previous = $previous;
		$this->allowComments = $allowComments;
		$this->deleted = $deleted;
		$this->etag = $etag;
		$this->lastModified = $lastModified;
	}
	
	function toJSON() {
		$a = $this->toArray();
		$out = array("Announcement"=>$a);
		return json_encode($out);
	}
	
	function toArray() {
		$a = array(
				"Id"=>$this->announcementID,
				"UserID"=>$this->userID,
				"DatePosted"=>$this->datePosted,
				"Headline"=>$this->headline,
				"Body"=>$this->body,
				"Previous"=>$this->previous,
				"AllowComments"=>$this->allowComments,
				"Deleted"=>$this->deleted,
				"Etag"=>$this->etag,
				"LastModified"=>$this->lastModified
		);
		return $a;
	}
	
// Getters
	function getAnnouncementID() {
		return $this->announcementID;
	}
	
	function getUserID() {
		return $this->userID;
	}
	
	function getDatePosted() {
		return $this->datePosted;
	}
	
	function getHeadline() {
		return $this->headline;
	}
	
	function getBody() {
		return $this->body;
	}
	
	function getPrevious() {
		return $this->previous;
	}
	
	function allowComments() {
		return $this->allowComments;
	}
	
	function isDeleted() {
		return $this->deleted;
	}
	
	function getEtag() {
		return $this->etag;
	}
	
	function getLastModified() {
		return $this->lastModified;
	}
// Setters
	function setAnnouncementID($newID) {
		$this->announcementID = $newID;
	}
	
	function setUserID($newID) {
		$this->userID = $newID;
	}
	
	function setDatePosted($date) {
		$this->datePosted = $date;
	}
	
	function setHeadline($headline) {
		$this->headline = $headline;
	}
	
	function setBody($body) {
		$this->body = $body;
	}
	
	function setPrevious($previous) {
		$this->previous;
	}
		
	function setAllowComments($boolean) {
		$this->allowComments = $boolean;
	}
	
	function setDeleted($boolean) {
		$this->deleted = $boolean;
	}

	function setEtag($etag) {
		$this->etag = $etag;
	}
	
	function setLastModified($lastModified) {
		$this->lastModified = $lastModified;
	}
	
// Components
	// From announcement table
	private $announcementID;
	private $userID;
	private $datePosted;
	private $headline;
	private $body;
	private $previous;			// announcementID_FK
	private $allowComments;		// boolean
	private $deleted;			// boolean
	private $etag;
	private $lastModified;
	
}