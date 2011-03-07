<?php
/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */
 
namespace Core\Utils;

class Bots {
    public static function is_bot($user_agent=False) {
        $engines = array('Googlebot', 'Yammybot', 'Openbot', 'Yahoo', 'Slurp', 'msnbot','ia_archiver', 'Lycos', 'Scooter', 'AltaVista', 'Teoma', 'Gigabot','Googlebot-Mobile');
        if(!$user_agent) {
             $user_agent=$_SERVER['HTTP_USER_AGENT'];
        }
        if(preg_match('/' . $engines . '/', $user_agent) > 0) {
            return True;
        } else {
            return False;
        }
    }
}