<?php

define("DIRSEP", DIRECTORY_SEPARATOR);
define("LOCALE", "en_GB");
define("SITE_PATH", realpath(dirname(__FILE__) . DIRSEP . '..' . DIRSEP) . DIRSEP);
define("BASE_HREF", preg_replace("/(.*?)\/index.php/", "$1", $_SERVER['PHP_SELF']));
define("CONFIG_PATH", SITE_PATH . DIRSEP . "config");
define("HOST", $_SERVER["HTTP_HOST"]);

# If this is set to 1, searching will far faster but less det
# -ailed. (Using mysql full text natural searching). This for
# if there are many articles.
define("QUICK_SEARCH", 0);

$imported_files = array();
$include_paths = array_merge(array(__DIR__), explode(':', ini_get('include_path')));

ini_set('include_path', implode(':', $include_paths));

class ImportError extends Exception {
	public function __construct($class) {
		trigger_error(sprintf('Class "%s" was not found in any of available paths.', $class), E_USER_ERROR);	
	}
}

function import($module_name) {
	global $imported_files;
	global $include_paths;

	$module = explode('.', $module_name);
	$l = array_pop($module);

	foreach($include_paths as $include_path) {
		if($l == '*'){
			$dir = $include_path . DIRSEP . implode(DIRSEP, $module) . DIRSEP;
			if(!is_dir($dir)){
				continue;
			} else {
				foreach(glob( $dir . '*.php') as $file) { 
		   			include_once $file;
				}
		   		break;
			}
		} else {
			$mp = $include_path . DIRSEP . implode(DIRSEP, $module) . DIRSEP . $l . '.php';
			if(in_array($mp,$imported_files)) {
				break;
			} else {
				if(file_exists($mp)){
					include_once strtolower($mp);
					$imported_files[] = $mp;
					break;
				}
			}
		}
		throw new ImportError($module_name);
	}
}

?>