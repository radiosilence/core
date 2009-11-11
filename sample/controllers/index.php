<?php

class controller_index extends controller
{
	public function index( $args )
	{
		$view = new view;
		
		include SITE_PATH . DIRSEP . "languages" . LOCALE . "sample.php";
		
		$view->set( "sample", L_SAMPLE );
		$view->show( "sample" );
	}
}

?>