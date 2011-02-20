<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

 namespace Core\Utils;

 class Language {
     public static function plural($word) {
         return $word . 's';
     }
 }