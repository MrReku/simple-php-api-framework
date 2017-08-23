<?php

/* # API Params Call
 * service 		: name of the service we want to use 	(required)
 * endpoint		: method of the service we want to use  (required)
 * token_id		: encripted user id 					(if needed)
 * secret_id	: encripted password 					(if needed)
 *
 * # Methods
 * add 						: Add array or string to the set results
 * throw_error				: throw an error
 * create_api_access		: Create a new API User
 * verify_credentials		: Verify if you have rights for the service (Token and Secret ID required)
 *
 * # Reserved Endpoint
 * authentication			: Reserved Endpoint for authentication, returns true if authenticated. 
 *
 * # Other
 * DB_access_id	:  return the id from authorised user 
 * DB_credential:  return the Info about the user created like token, secret and username 
 */
  
Class API {

	private	$API_config_path	= 'config/',
			$API_output 		= array(),
			$DB_database		= 'your_db_name_here',
			$DB_host			= 'your_db_host_here',
			$DB_user			= 'your_db_user_here',
			$DB_password		= 'your_db_psw_here';
			
	public	$API_enviroment 	= array(
						
								"request"	=> array(),
								"error"		=> array(),
			),
			$API_config 		= array(),
			$API_suffix			= 'API_',
			$API_auth			= false,
			$API_is_validated	= false,
			$API_show_env		= false,			/* Used to show the Enviroment in the output response */
			$API_show_db_env	= false,
			$API_service_name,
			$API_endpoint,
			$API_status,
			$DB_access_id,
			$DB_access_name,
			$DB_credential;


	function __construct($service_name = false) {
		
		$this->API_enviroment['request'] = $_GET;
		
		!$service_name ? $this->error_handler(1) : $this->startup($service_name);		
		
		if ( !function_exists('http_response_code') ) {
			
			function http_response_code($code = NULL) {
			
				if ($code !== NULL) {
	
					switch ($code) {
						
						case 100: $text = 'Continue'; break;
						case 101: $text = 'Switching Protocols'; break;
						case 200: $text = 'OK'; break;
						case 201: $text = 'Created'; break;
						case 202: $text = 'Accepted'; break;
						case 203: $text = 'Non-Authoritative Information'; break;
						case 204: $text = 'No Content'; break;
						case 205: $text = 'Reset Content'; break;
						case 206: $text = 'Partial Content'; break;
						case 300: $text = 'Multiple Choices'; break;
						case 301: $text = 'Moved Permanently'; break;
						case 302: $text = 'Moved Temporarily'; break;
						case 303: $text = 'See Other'; break;
						case 304: $text = 'Not Modified'; break;
						case 305: $text = 'Use Proxy'; break;
						case 400: $text = 'Bad Request'; break;
						case 401: $text = 'Unauthorized'; break;
						case 402: $text = 'Payment Required'; break;
						case 403: $text = 'Forbidden'; break;
						case 404: $text = 'Not Found'; break;
						case 405: $text = 'Method Not Allowed'; break;
						case 406: $text = 'Not Acceptable'; break;
						case 407: $text = 'Proxy Authentication Required'; break;
						case 408: $text = 'Request Time-out'; break;
						case 409: $text = 'Conflict'; break;
						case 410: $text = 'Gone'; break;
						case 411: $text = 'Length Required'; break;
						case 412: $text = 'Precondition Failed'; break;
						case 413: $text = 'Request Entity Too Large'; break;
						case 414: $text = 'Request-URI Too Large'; break;
						case 415: $text = 'Unsupported Media Type'; break;
						case 500: $text = 'Internal Server Error'; break;
						case 501: $text = 'Not Implemented'; break;
						case 502: $text = 'Bad Gateway'; break;
						case 503: $text = 'Service Unavailable'; break;
						case 504: $text = 'Gateway Time-out'; break;
						case 505: $text = 'HTTP Version not supported'; break;
						default:
							exit('Unknown http status code "' . htmlentities($code) . '"');
						break;
					}					
					$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');					
					header($protocol . ' ' . $code . ' ' . $text);
					$GLOBALS['http_response_code'] = $code;
				} else {
	
					$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
				}
				
				return $code;
			}
		}
	}

	private function error_handler($error_code) {
		
		switch ( $error_code ) {

			case 1:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'No Service';
			break;
			case 2:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Unknown Service';
			break;
			case 3:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Api module not found';
			break;
			case 4:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Endpoint missing';
			break;
			case 5:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Endpoint not found';
			break;
			case 6:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Wrong Endpoint';		
			break;
			case 7:
				$this->API_status = 401;			
				$this->API_enviroment['error'] = 'Unauthorised Access, Either missing or wrong Token or Secret';		
			break;
			case 8:
				$this->API_status = 401;			
				$this->API_enviroment['error'] = 'Unauthorised Access.';		
			break;
			case 9:
				$this->API_status = 500;			
				$this->API_enviroment['error'] = 'Unable to create an Access';		
			break;
			case 10:
				$this->API_status = 400;			
				$this->API_enviroment['error'] = 'Argument Missing';		
			break;
			case 11:
				$this->API_status = 503;			
				$this->API_enviroment['error'] = 'Service not Available due to an internal Error.';		
			break;

			default:
				$this->API_status = 200;			
			break;			
		}
	}

	
	private function startup($service_name) {
	
		$this->API_service_name = $service_name;
		
		if ( $this->load_config($service_name) ) {
			
			if ( $this->request_validation() ) {
				
				$this->API_is_validated = true;
			}
		}
	}
	
	
	private function load_config($service_name) {

		$service_path = $this->API_config_path.$service_name.'.config.json';
		
		if ( file_exists($service_path) ) {
			
			$this->API_config = json_decode(file_get_contents($service_path), true);
			
			if ( !array_key_exists( 'authentication', $this->API_config['methods'] ) 		&&
				 !array_key_exists( 'authentication', $this->API_config['open_methods'] ) 	){
						
				return true;			
			} else {
				
				$this->error_handler(11);
			
				return false;
			}
		} else {
			
			$this->error_handler(2);
			
			return false;
		}
	}

	
	private function request_validation() {
			
		if ( !file_exists($this->API_config['source_path']) ) {
			
			$this->error_handler(3);
		} 
				
		if ( !$this->API_enviroment['request']['endpoint'] ) {
			
			$this->error_handler(4);
		} else {
						
			!array_key_exists( $this->API_enviroment['request']['endpoint'], $this->API_config['methods'] ) 		&&
			!array_key_exists( $this->API_enviroment['request']['endpoint'], $this->API_config['open_methods'] ) 	&&
			$this->API_enviroment['request']['endpoint'] !== 'authentication'										?
			$this->error_handler(5) 																				:
			$this->methods_allowance();
		}		
		
		return ( count($this->API_enviroment['error']) == 0 ) ? true : false;		
	}


	private function methods_allowance() {
		
		$this->API_auth	= $this->API_config['protected'];
						
		if ( array_key_exists( $this->API_enviroment['request']['endpoint'], $this->API_config['open_methods'] ) ) {
			
			$this->API_endpoint = $this->API_enviroment['request']['endpoint'];
		} 
		else if ( array_key_exists( $this->API_enviroment['request']['endpoint'], $this->API_config['methods'] ) ) {	
		
			$this->API_auth ? $this->authentication_via_db() : $this->API_endpoint = $this->API_enviroment['request']['endpoint']; 
		}
		else if ( $this->API_enviroment['request']['endpoint'] == 'authentication' ) {
			
			$this->authentication_via_db();
		} else {
			
			$this->error_handler(6);
		}
	}


	private function authentication_via_db() {
		
		if ( !$this->API_enviroment['request']['token_id'] 		||
			 !$this->API_enviroment['request']['secret_id'] ) 	{
			
			$this->error_handler(7);
		} else {
			
			include_once('lib/db.class.php');
			$this->db = new DB($this->DB_host, $this->DB_user, $this->DB_password, $this->DB_database, $this->API_service_name, $this->API_suffix);
			
			if ( $this->db->check_auth($this->API_enviroment['request']['token_id'], $this->API_enviroment['request']['secret_id']) ) {
				
				$this->API_endpoint 	= $this->API_enviroment['request']['endpoint'];	
				$this->DB_access_id		= $this->db->access_id;
				$this->DB_access_name 	= $this->db->access_name;				
			} else {

				$this->API_enviroment['debug_db'] = $this->db->report;
				$this->error_handler(8);
			}
		}
	}
	
	
	public function add( $data = array(), $node = "response" ) {
		
		if ( count( $this->API_enviroment['error'] ) == 0 ) {
			
			$this->API_output = array_merge( $this->API_output, array( $node => $data ) );
		} 
	}


	public function create_api_access( $token, $secret, $name ) {
		
		if ( $token && $secret ) {
			
			include_once('lib/db.class.php');
			$this->db = new DB($this->DB_host, $this->DB_user, $this->DB_password, $this->DB_database, $this->API_service_name, $this->API_suffix);
			
			if ( $this->db->create_auth($token, $secret, $name) ) {
				
				$this->API_enviroment['debug_db'] 	= $this->db->report.' ID: '.$this->db->access_id;
				$this->DB_access_id					= $this->db->access_id;
				$this->DB_access_name				= $this->db->access_name;
				$this->DB_credential				= $this->db->credential;
			} else {

				$this->API_enviroment['debug_db'] 	= $this->db->report;
				$this->error_handler(9);					
			}
		} else {
			
			$this->error_handler(10);	
		}
	}


	public function verify_credentials() {
		
		if ( $this->API_enviroment['request']['token_id'] && $this->API_enviroment['request']['secret_id'] ) {

			include_once('lib/db.class.php');
			$this->db = new DB($this->DB_host, $this->DB_user, $this->DB_password, $this->DB_database, $this->API_service_name, $this->API_suffix);
			
			if ( !$this->db->check_auth($this->API_enviroment['request']['token_id'], $this->API_enviroment['request']['secret_id']) ) {

				$this->API_enviroment['debug_db'] = $this->db->report;
				$this->error_handler(7);
			} else {
				
				$data = true;
				$this->add($data, 'authenticated');
			}
		} else {
			
			$this->error_handler(10);	
		}
	}


	public function throw_error( $message ) {
		
		$this->API_enviroment['error'] 	= $message;
		$this->API_status 				= 400;			
	}


	private function clear_empty_array_keys($array_to_clean){
		
		foreach ( $array_to_clean as $i => $v ) {
			
			if ( empty( $array_to_clean[$i] ) ) {
        
    			unset( $array_to_clean[$i] );
    		} 
		}
		
		return $array_to_clean;
	}


	public function publish($data = false) {
		
		$data_output = array();
				
		if ( !$this->API_show_db_env ){
			
			unset($this->API_enviroment['debug_db']);
		}
		
		if ( !count( $this->API_enviroment['error'] ) == 0 ) {
		
			$data_output = $this->API_enviroment;
		} else {
						
			if ( $this->API_show_env == true ) {
				
				$this->API_enviroment 	= $this->clear_empty_array_keys($this->API_enviroment);
				$data_output 			= array_merge($this->API_enviroment, $this->API_output);
			} else {
				
				$data_output = $this->API_output;
			}			
		}
		
		$data_output = json_encode($data_output);
		
		if ( $this->API_enviroment['request']['callback'] 				&& 
			 strlen($this->API_enviroment['request']['callback']) > 0 ) {
			
			$data_output = $this->API_enviroment['request']['callback'].'('.$data_output.');';
		}
		
		http_response_code($this->API_status);		
		header("Content-Type: application/json");
		echo $data_output;
	}
}


$API = new API($_GET["service"]);

if ( $API->API_is_validated ) {
	
	$API->API_endpoint == 'authentication' ? $API->verify_credentials() : include_once($API->API_config['source_path']);
}

$API->publish();
?>