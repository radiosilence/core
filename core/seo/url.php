<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Core\SEO;

class URL {
	public function __construct($page) {
		$t = $page["title"];
		$i = $page["id"];
		$this->url = sprintf("%d/%s.html", $i, $this->process_string($t));
	}
	public function __toString() {
		return $this->url;
	}
	private function process_string($string) {
		$a = array( 
			" ", "_"
		);
		$b = "-";
		
		$string = str_replace($a, $b, strtolower($string));
		return preg_replace("/[^a-z0-9\-]/", "", $string);;
	}
}
?>