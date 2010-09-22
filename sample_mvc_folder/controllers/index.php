<?php

namespace Controllers;

import('core.view');

class index extends \Core\Controller {
	public function index($args) {
		$view = new \Core\View();
		
		$this->load_locale("sample");
		
		$view->set("sample", L_SAMPLE);
		$view->show("sample");
	}
}

?>