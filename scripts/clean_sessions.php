<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * This cleans the sessions that have gone thirty days without activity.
 */

require(__DIR__ . '/../core.php');
require(CONFIG_PATH.DIRSEP . 'database.php');

if($config_db['driver'] == 'mysql') {    
    $pdo = new PDO(sprintf('%s:host=%s;dbname=%s',
            $config_db['driver'],
            $config_db['host'],
            $config_db['database']),
        $config_db['user'],
        $config_db['password']
    );
} else {
    $pdo = new PDO(sprintf('%s:host=%s;dbname=%s;user=%s;password=%s',
        $config_db['driver'],
        $config_db['host'],
        $config_db['database'],
        $config_db['user'],
        $config_db['password']
    ));    
}

var_dump($sth->fetchAll());

$sth = $pdo->prepare("
    SELECT
      *
    FROM
      sessions
    WHERE
        timestamp(latest) <  - interval '30 days'
");

$sth->execute();


var_dump($sth->fetchAll());

$sth = $pdo->prepare('
    DELETE FROM
      sessions
    WHERE
      age(latest) > 30
')->execute();

?>