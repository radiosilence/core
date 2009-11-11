<?php
/**
 * Extends mysqli to do interesting
 * things, should be useful for prepared
 * statements, etc.
 *
 * @package database
 * @subpackage core
 */
class db_mysqli extends mysqli
{
	public static $total_queries = 0;
	public static $total_time = 0;
	private $query;

	function query( $query )
	{
		if(DEBUG) DB_MYSQLI::$total_queries++;

		if(DEBUG) $time_start = microtime(true);

		$result = mysqli_query( $this, $query );

		if(DEBUG) $time_end = microtime(true);
		if(DEBUG) FB::send( sprintf( "%s", $query ), "» mysqli Query [#" . DB_MYSQLI::$total_queries . "]" );
		if(DEBUG) FB::send( round( ( $time_end - $time_start ) * 1000, 2 ) . "ms", "» └ Time" );

		DB_MYSQLI::$total_time += $time_end - $time_start;

		return $result;
	}

	public function build_query()
	{
		$this->query = new query( $this );
		return $this->query;
	}
	
	public function append_query()
	{
		return $this->query;
	}

	public function run_query( &$result )
	{

		if( $result = $this->query( $this->query ) )
		{
			unset( $this->query );
			return 1;
		}
		else
		{
			$this->print_query();
			trigger_error('Query failure.' . $this->error, E_USER_ERROR);
			unset( $this->query );
			return 0;
		}

	}

	public function print_query( $query = 0 )
	{
		$query = $query == 0 ? $query : $this->query;
		printf("<pre>Query output:\n%s\n</pre>", $this->query );
	}
	
	public function get_query( $query = 0 )
	{
		$query = $query == 0 ? $query : $this->query;
		return sprintf("%s", $this->query );
	}
	public function prepare()
	{
		if( func_num_args() > 0 )
		{
			$query = func_get_args();
			$query = $query[ 0 ];
		}
		else
		{
			$query = $this->query;
		}
		return mysqli_prepare( $this, $query ? $query : sprintf( "%s", $this->query ) );
	}
}
?>