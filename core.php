<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if(version_compare(phpversion(), "5.3.1", "<")) {
	trigger_error(sprintf('Needs to use PHP at least version 5.3, you are using version %s.', phpversion()), E_USER_ERROR);
}

define("DEBUG", True);
define("LOCALE", "en_GB");

define("SITE_PATH", dirname($_SERVER["SCRIPT_FILENAME"]) . '/..' );
define("BASE_HREF", preg_replace("/(.*?)\/index.php/", "$1", $_SERVER['PHP_SELF']));
define("CACHE_PATH", SITE_PATH . "/.cache/");
define("HOST", $_SERVER["HTTP_HOST"]);
define("URI", $_GET['route']);


if(extension_loaded('memcached')) {
    define('EXT_MC', True);
}

require('importer.php');

IMPORTER::instantiate();

function import($module_name) {
	IMPORTER::import_module($module_name);
}
