<?php
class seo_url
{
	public function __construct( $page )
	{
		$t = $page[ "title" ];
		$i = $page[ "id" ];
		$this->url = sprintf( "%d/%s.html", $i, $this->process_string( $t ) );
	}
	private function process_string( $string )
	{
		$a = array( 
			" ", "?", "!", "&", "_", ":", "#",
		);
		$b = array( 
			"-", null, null, null, "-", null, null,
		);
		
		$string = str_replace( $a, $b, strtolower( $string ) );
		return $string;
	}
}
?>