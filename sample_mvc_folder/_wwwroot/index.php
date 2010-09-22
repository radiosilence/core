<?php
/**
 * Sample index.php for working with core. Should be copied
 * to ../_wwwroot, and this should be set as the web root.
 * Also copy the included .htaccess if using apache.
 * Also recommended:
 * - ../config/database.php (if you want to use database)
 * - ../controllers/index.php
 */

# Definitions
define("DEBUG", 0);

require("../core/core.php");

import('core.router');

# Load router
$router = new Router();
$router->set_path(SITE_PATH . 'controllers');
$router->delegate();
?>