<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if(phpversion() < 5.3) {
	trigger_error(sprintf('Needs to use PHP at least version 5.3, you are using version %s.', phpversion()), E_USER_ERROR);
}

define("DIRSEP", DIRECTORY_SEPARATOR);
define("LOCALE", "en_GB");
define("SITE_PATH", realpath(dirname(__FILE__) . DIRSEP . '..' . DIRSEP) . DIRSEP);
define("BASE_HREF", preg_replace("/(.*?)\/index.php/", "$1", $_SERVER['PHP_SELF']));
define("CONFIG_PATH", SITE_PATH . "config");
define("HOST", $_SERVER["HTTP_HOST"]);

require('importer.php');

IMPORTER::instantiate();

function import($module_name) {
	IMPORTER::import_module($module_name);
}


?>
