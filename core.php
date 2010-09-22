<?php
/* Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY James Cleveland "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland. */

if(phpversion() < 5.3) {
	trigger_error(sprintf('Needs to use PHP at least version 5.3, you are using version %s.', phpversion()), E_USER_ERROR);
}

define("DIRSEP", DIRECTORY_SEPARATOR);
define("LOCALE", "en_GB");
define("SITE_PATH", realpath(dirname(__FILE__) . DIRSEP . '..' . DIRSEP) . DIRSEP);
define("BASE_HREF", preg_replace("/(.*?)\/index.php/", "$1", $_SERVER['PHP_SELF']));
define("CONFIG_PATH", SITE_PATH . DIRSEP . "config");
define("HOST", $_SERVER["HTTP_HOST"]);

require('importer.php');
$importer = new Importer();
$importer->set_include_paths();

function import($module_name) {
	global $importer;
	$importer->import_module($module_name);
}


?>
