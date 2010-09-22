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

# Load router
$router = new router();
REGISTRY::set('router', $router);
$router->set_path(SITE_PATH . 'controllers');
$router->delegate();
?>