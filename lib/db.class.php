<?php
/**
 * --- API DB CLASS -----------------------------------------------------------------------------------------------------------------------------
 * 
 * Methods:
 * 
 * check_auth($token, $secret)
 * create_auth($token, $secret)
 * 
 * 
 * ------------------------------------------------------------------------------------------------------------------------------------------------------
 * @Last Modified: wed, Apr 2nd, 2014
 */
 
class DB extends mysqli {

	public 	$user,
			$db,
			$host,
			$password,
			$report,
			$result,
			$suffix,
			$service,
			$access_id,
			$access_name,
			$credential;
			
	private $unique_salt;


	function __construct( $DB_host, $DB_user, $DB_psw, $DB_database, $DB_service, $DB_suffix ){
		
		$this->suffix	= $DB_suffix;
		$this->service	= $DB_service;
		$this->password	= $DB_psw;
		$this->host		= $DB_host;
		$this->user		= $DB_user;
		$this->db		= $DB_database;
	}


	public function check_auth($token, $secret) {
		
		if ( !$this->table_check($this->suffix.$this->service) ) {
			
			$this->report =	'Error: Table not found';
			
			return FALSE;
		} else {
			
			if ( !$this->sanitize($token) || !$this->sanitize($secret) ) {
				
				$this->report =	'Either Malformed Token or Secret';
				return FALSE;
			}
			
			if ( $this->username_existing($token) ) {
						
				$qr 		= "SELECT * FROM ".$this->suffix.$this->service." WHERE token_id = '$token'";	
				$check_user = $this->getRow($qr, TRUE);			
	
				if ( $secret == $check_user->secret_id ) {
					
					$this->report 		= 'Successful Logged in.';
					$this->access_id	= $check_user->id;
					$this->access_name	= $check_user->access_name;
					return TRUE;			
				} else {
					
					$this->report = 'Wrong Secret.';				
					return FALSE;
				}		
			} else {
			
				$this->report = 'Wrong Token.';
				return FALSE;		
			}
		}
	}


	public function create_auth($token, $secret, $name) {
		
		if ( !$this->table_check($this->suffix.$this->service) ) {
	
			$this->create_table($this->suffix.$this->service);	
		}
	
		if ( !$this->verify_exsisting_user(md5($token)) ) {
			
			return FALSE;		
		} else {
		
			$token_id 	= md5($token);
			$secret_id 	= $this->psw_coding($secret);

			$query = "INSERT INTO ".$this->suffix.$this->service." 
					(token_id, secret_id, access_name) 
					VALUES ('$token_id', '$secret_id', '$name')";
			
			if ( $this->insRow($query) ) {
				$this->report = "User successful created.";
				$this->credential = array("token_id" => $token_id, "secret_id" => $secret_id, "access_name" => $name );
				return TRUE;
			} else {
			
				$this->report = "Error Creating User.";
				return FALSE;
			}
		}
	}
		
	
	private function sanitize($string){
		
		preg_match( '/[^a-zA-Z0-9]+/', trim($string), $check);
		
		return ( empty($check) && trim($string) !== '' ) ? TRUE : FALSE;
	}	
	
	private function table_check($table_name) {
		
		$qr = "	SELECT COUNT(*) AS table_num
				FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = '{$this->db}' 
				AND TABLE_NAME = '$table_name'";	

		$count = $this->getRow($qr);
		return (bool)$count->table_num;
	}
	
	
	private function create_table($table_name) {
		
		$qr = "CREATE TABLE ".$table_name." ( id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, token_id VARCHAR(255), secret_id VARCHAR(255), access_name VARCHAR(255) )";
		$this->exec_sql($qr);
	}
	
	
	private function verify_exsisting_user($username) {
	
		if ( $this->username_existing($username) ) {
	
			$this->report = 'Username already Used.';
			return FALSE;
	
		} else {
			
			return TRUE;
		}
	}
	
	
	private function username_existing($token) {
		
		$qr = "SELECT * FROM ".$this->suffix.$this->service." WHERE token_id = '$token'";

 		if ( $this->findRow($qr) ) {
 			
 			return TRUE;
 		}
	}
	
	
	private function psw_retrieve($password, $salt) {
		
		return sha1($salt . md5($password));
	}
		
	private function psw_coding($password) {
		
		return sha1($this->unique_salt() . md5($password));
	}
	
	
	private function unique_salt() {
	
		$this->unique_salt = substr(sha1(mt_rand()),0,22);
		return $this->unique_salt; 
	}
	
	
	private function connectDb() {
		
		@parent::connect($this->host, $this->user, $this->password, $this->db);		
		
		if ( mysqli_connect_error() ) {
    		
    		$this->report = 'Connect Error (' .mysqli_connect_errno() . ') '.mysqli_connect_error();
    		return false;
		}
	}
	
		
	private function closeDb($closing) {
		
		if (!$closing) { 
			
			parent::close();
		} else {
			$this->report = 'Connection still alive.'; 
		}
	}
	
	
	private function doQuery($query) {
	
		$this->connectDb();
		
		if ( !$this->result = @parent::query($query) ) {
			
			$this->report = 'Invalid Query: '.mysqli_error($this).' Line: ('.__LINE__.')  Function: ('.__FUNCTION__.') Class: ('.__CLASS__.')';
			return FALSE;
		} else {
			
			return TRUE;
		}
	}
	
	
	private function getRow($query, $closing = FALSE) {
		
		$this->doQuery($query);
		$row = @mysqli_fetch_object($this->result);
		$this->closeDb($closing);
		return $row;
	}
	
	
	private function findRow($query, $closing = FALSE) {
		
		$this->doQuery($query);
		$row = @mysqli_num_rows($this->result);
		$this->closeDb($closing);
		if( $row > 0 ) {
			
			return TRUE;
		} else {
			
			return FALSE;
		}
	}
	
	
	private function exec_sql($query, $closing = FALSE) {
		
		if ( $this->doQuery($query) ) {
			
			$this->closeDb($closing);
			return TRUE;
		}
	}
	
	
	public function lastId() {
		
		return @mysqli_insert_id($this);
	}
	
	
	public function insRow($query, $closing = FALSE) {
		
		if ( $this->doQuery($query) ) {
			
			$this->access_id = $this->lastId();
			$this->closeDb($closing);
			return TRUE;
		}
	}
}
?>