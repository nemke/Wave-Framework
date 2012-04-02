<?php

/* 
WWW - PHP micro-framework
Index gateway API handler

Index gateway API handler takes all the input from GET, POST, FILES, SESSION and COOKIE 
variables and sends an API command to WWW_API. By default it returns JSON formatted data. 
This script also loads WWW_Logger, WWW_Limiter and WWW_Database for additional functionality. 
Sending www-command as an input variable (GET, POST and so on) will attempt to execute that 
command through API and return appropriate JSON encoded data. Other returned data formats 
are also possible to be used, if set by www-return-data-type, such as xml, text or 
serializedarray.

Author and support: Kristo Vaher - kristo@waher.net
*/

//INITIALIZATION

	// Stopping all requests that did not come from Index gateway
	if(!isset($resourceAddress)){
		header('HTTP/1.1 403 Forbidden');
		die();
	}

	// This functions file is not required, but can be used for system wide functions
	// If you want to include additional libraries, do so here
	require(__ROOT__.'resources'.DIRECTORY_SEPARATOR.'functions.php');

	// State class is used by API and Factory created objects to keep track of request state
	require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-state.php');
	$state=new WWW_State($config);
	
// DATABASE

	// If database name is set then database controller is loaded
	if(isset($config['database-name']) && $config['database-name']!='' && isset($config['database-type']) && isset($config['database-host']) && isset($config['database-username']) && isset($config['database-password'])){
		// Including the required class and creating the object
		require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');
		$databaseConnection=new WWW_Database();
		// Assigning database variables and creating the connection
		$databaseConnection->type=$config['database-type'];
		$databaseConnection->host=$config['database-host'];
		$databaseConnection->username=$config['database-username'];
		$databaseConnection->password=$config['database-password'];
		$databaseConnection->database=$config['database-name'];
		$databaseConnection->connect();
		// Passing the database to State object
		$state->databaseConnection=$databaseConnection;
	}
	
// LOADING API AND GATHERING INPUT DATA
	// API is used to process all requests and it handles caching and API validations
	require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-api.php');
	$api=new WWW_API($state);

	// All the data sent by the client is stored in this variable
	$inputData=array();

	// All the data sent by client is added here and merged into one array
	if(isset($_POST) && !empty($_POST)){ 
		$inputData+=$_POST; 
	}
	if(isset($_GET) && !empty($_GET)){ 
		$inputData+=$_GET; 
	}
	if(isset($_SESSION) && !empty($_SESSION)){ 
		$inputData['www-session']=$_SESSION; 
	}
	if(isset($_FILES) && !empty($_FILES)){ 
		$inputData['www-files']=$_FILES;
		// If files are defined, then API is told not to use cache
		$inputData['www-no-cache']=true; 
	}
	if(isset($_COOKIES) && !empty($_COOKIES)){ 
		$inputData['www-cookies']=$_COOKIES; 
	}
	
// SENDING COMMAND TO API

	// Setting current API profile in state
	if(isset($inputData['www-profile'])){
		$state->data['api-profile']=$inputData['www-profile'];
	} else {
		$state->data['api-profile']=$state->data['api-public-profile'];
	}
	
	// API command is executed with all the data that was sent by the client, along with other www-* settings
	$apiResult=$api->command($inputData,false,true,true);
	
// LOGGER

	// Logger notifications
	if(isset($logger)){
		$logger->setCustomLogData($api->apiLoggerData+array('category'=>'API['.$apiHandler.']','api-profile'=>$state->data['api-profile'],'database-query-count'=>(($databaseConnection)?$databaseConnection->queryCounter:0)));
		$logger->writeLog();
	}

?>