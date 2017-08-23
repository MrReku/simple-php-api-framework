<?
switch ($API->API_endpoint) {
	
	default: 
		
		$data = 'empty';			
	break;

	case 'protected_test': 
		
		$data = 'We swore to protect.';			
	break;
	
	case 'open_test': 
	
		$data = 'Hello Open World.';	
	break;
}

/* Show the Enviroment info */
$API->API_show_env 	= false;

/* Add to response */
$API->add($data);
?>