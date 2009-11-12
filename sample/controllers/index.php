<?php

class controller_index extends controller
{
	public function index( $args )
	{
		$view = new view( $this->registry );
		
		$this->load_locale( "sample" );
		
		$view->set( "sample", L_SAMPLE );
		$view->show( "sample" );
	}
}

?>