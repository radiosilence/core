<?php

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