<?php/* WWW FrameworkRobots handlerIt is always good to add robots.txt to any web service or website, even if search engine robots may not always follow these rules. This file can be used to restrict access to specific URL's from being cached until service is ready to go live. If robots.txt does not exist in root folder of the website, then this script here generates it. Otherwise it simply returns the file contents.* Values here can be overwritten by /resources/{language-code}.sitemap.php filesAuthor and support: Kristo Vaher - kristo@waher.net*/// INITIALIZATION	// Stopping all requests that did not come from Index gateway	if(!isset($resourceAddress)){		header('HTTP/1.1 403 Forbidden');		die();	}	// Robots.txt file is always returned in plain text format	header('Content-Type: text/plain;charset=utf-8;');		// This flag stores whether cache was used	$cacheUsed=false;// GENERATING ROBOTS FILE	// Robots file is generated only if it does not exist in root	if(!file_exists(__ROOT__.'robots.txt')){			// ASSIGNING PARAMETERS FROM REQUEST			// If filename includes & symbol, then system assumes it should be dynamically generated			$parameters=array_unique(explode('&',$resourceFile));			// Looking for cache			$cacheFilename=md5('robots.txt'.$resourceRequest).'.tmp';			$cacheDirectory=__ROOT__.'filesystem'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.substr($cacheFilename,0,2).DIRECTORY_SEPARATOR;			// If cache file exists then cache modified is considered that time			if(file_exists($cacheDirectory.$cacheFilename)){				$lastModified=filemtime($cacheDirectory.$cacheFilename);			} else {				// Otherwise it is server request time				$lastModified=$_SERVER['REQUEST_TIME'];			}			// Default cache timeout of one month, unless timeout is set			if(!isset($config['robots-cache-timeout'])){				$config['robots-cache-timeout']=14400; // Four hours			}					// GENERATING NEW ROBOTS FILE OR LOADING FROM CACHE			// If robots cannot be found from cache, it is generated			if(in_array('nocache',$parameters) || ($lastModified==$_SERVER['REQUEST_TIME'] || $lastModified<($_SERVER['REQUEST_TIME']-$config['robots-cache-timeout']))){							// STATE AND DATABASE									// State stores a lot of settings that are taken into account during Sitemap generation					require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-state.php');					$state=new WWW_State($config);					// Connecting to database, if configuration is set					if(isset($config['database-name']) && isset($config['database-type']) && isset($config['database-host']) && isset($config['database-username']) && isset($config['database-password'])){						// Including the required class and creating the object						require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');						$databaseConnection=new WWW_Database();						// Assigning database variables and creating the connection						$databaseConnection->type=$config['database-type'];						$databaseConnection->host=$config['database-host'];						$databaseConnection->username=$config['database-username'];						$databaseConnection->password=$config['database-password'];						$databaseConnection->database=$config['database-name'];						$databaseConnection->connect();					}									// GENERATING ROBOTS STRING 									// Robots string is stored here					$robots='';					$robots.='User-agent: *'."\n";					$robots.='Disallow: '."\n";					$robots.='Sitemap: '.((isset($config['https-limiter']) && $config['https-limiter']==true)?'https://':'http://').$_SERVER['HTTP_HOST'].$state->data['web-root'].'sitemap.xml';									// WRITING TO CACHE								// Resource cache is cached in subdirectories, if directory does not exist then it is created					if(!is_dir($cacheDirectory)){						if(!mkdir($cacheDirectory,0777)){							throw new Exception('Cannot create cache folder');						}					}					// Data is written to cache file					if(!file_put_contents($cacheDirectory.$cacheFilename,$robots)){						throw new Exception('Cannot create resource cache');					}						} else {				// Setting the flag for logger				$cacheUsed=true;			}					// HEADERS					// If cache is used, then proper headers will be sent			if(in_array('nocache',$parameters)){				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age=0,must-revalidate');				header('Expires: '.gmdate('D, d M Y H:i:s',$_SERVER['REQUEST_TIME']).' GMT');				header('Last-Modified: '.$expiresDate.' GMT');			} else {				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age='.($lastModified+$config['robots-cache-timeout']-$_SERVER['REQUEST_TIME']).',must-revalidate');				header('Expires: '.gmdate('D, d M Y H:i:s',($lastModified+$config['robots-cache-timeout'])).' GMT');				header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			}			// Pragma header removed should the server happen to set it automatically			header_remove('Pragma');						// Content length of the file			$contentLength=filesize($cacheDirectory.$cacheFilename);			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);  					// OUTPUT			// Returning the file to user agent			readfile($cacheDirectory.$cacheFilename);			// File is deleted if cache was requested to be off			if(in_array('nocache',$parameters)){				unlink($cacheDirectory.$cacheFilename);			}			} else {				// RETURNING EXISTING ROBOTS FILE					// This is technically considered as using cache			$cacheUsed=true;					// Last modified header			header('Last-Modified: '.gmdate('D, d M Y H:i:s',filemtime(__ROOT__.'robots.txt')).' GMT');			// Content length of the file			$contentLength=filesize(__ROOT__.'robots.txt');			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);			// Since robots.txt did exist in root, it is simply returned			readfile(__ROOT__.'robots.txt');	}	// WRITING TO LOG	// If Logger is defined then request is logged and can be used for performance review later	if(isset($logger)){		// Assigning custom log data to logger		$logger->setCustomLogData(array('category'=>'robots','cache-used'=>$cacheUsed,'content-length-used'=>$contentLength,'database-query-count'=>(($databaseConnection)?$databaseConnection->queryCounter:0)));		// Writing log entry		$logger->writeLog();	}?>