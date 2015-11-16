<?php
spl_autoload_register(function($class) {
	$classPath = str_replace('\\','/', $class);
	$basePath = __DIR__.'/';

	if(file_exists($basePath.$classPath.'.class.php')) {
		include($basePath.$classPath.'.class.php');
	}
	elseif(file_exists($basePath.'PhpMvc/Modules/'.$classPath.'.class.php')) {
		include($basePath.'PhpMvc/Modules/'.$classPath.'.class.php');
	}
	else {
		throw new Exception(sprintf("Could not find class %s", $class));
	}
});

session_start();

$application = new PhpMvc\Core\Application();
$application->Run();
