<?php

define("DIRSEP", DIRECTORY_SEPARATOR);
define("LOCALE", "en_GB");
define("SITE_PATH", realpath(dirname(__FILE__) . DIRSEP . '..' . DIRSEP) . DIRSEP);
define("BASE_HREF", preg_replace("/(.*?)\/index.php/", "$1", $_SERVER['PHP_SELF']));
define("CONFIG_PATH", SITE_PATH . DIRSEP . "config");
define("CLASS_PATH", SITE_PATH . "core" . DIRSEP . "classes");
define("HOST", $_SERVER["HTTP_HOST"]);

# If this is set to 1, searching will far faster but less det
# -ailed. (Using mysql full text natural searching). This for
# if there are many articles.
define("QUICK_SEARCH", 0);

function __autoload($class_name) {

	if(strtolower(substr($class_name, 0, 5)) == "model"
		 && strtolower($class_name) != "model") {

		$class_path = SITE_PATH . "models";
		$class_name = substr($class_name, 5);

	} else {
		$class_path = CLASS_PATH;
	}
	
	$class = explode("_", $class_name);
	if(preg_match('/exception/i', $x = array_pop($class)) {
		array_push($class, "exceptions");
	} else {
		array_push($class, $x);
	}

	$filename = implode(DIRSEP, $class) . '.php';
	
	if(file_exists($class_path . DIRSEP . $filename)) {
		include ($class_path . DIRSEP . $filename);
	} else {
		throw new Exception("Could not find " . $class_path . DIRSEP . $filename . "!\n");
		return false;
	}

}

?>