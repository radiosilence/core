<?php
/**
 * Extends mysqli to do interesting
 * things, should be useful for prepared
 * statements, etc.
 *
 * @package database
 * @subpackage core
 */
class db_mssql
{
	private $connection = 0;
	public static $total_queries = 0;
	public static $total_time = 0;
	public $database = 0;
	public $query;

	public function __construct( $hostname, $username, $password, $database )
	{
		$this->connection = mssql_connect( $hostname, $username, $password );

		if( !$this->connection )
		{
			die( "Could not connect to database host [" . $hostname . "]." );
		}
		if( !mssql_select_db( $database, $this->connection ) )
		{
			die( "Could not select database [" . $database . "].");
		}

		$this->database = $database;
		return 1;
	}

	public function query( $query )
	{
		if(DEBUG) DB_MSSQL::$total_queries++;

		if(DEBUG) $time_start = microtime(true);

		$result = mssql_query( $query, $this->connection );

		if(DEBUG) $time_end = microtime(true);
		if(DEBUG) FB::send( sprintf( "%s", $query ), "» MSSQL Query [#" . DB_MSSQL::$total_queries . "]" );
		if(DEBUG) FB::send( round( ( $time_end - $time_start ) * 1000, 2 ) . "ms", "» └ Time" );

		DB_MSSQL::$total_time += $time_end - $time_start;

		return $result;
	}

	public function build_query()
	{
		$this->query = new query( $this );
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

	public function real_escape_string( $string )
	{
		# Not secure in any way ever.
		if(is_numeric($data))
		{
			return $data;
		}
		$unpacked = unpack('H*hex', $data);
		return '0x' . $unpacked['hex'];
	}

	public function print_query( $query = 0 )
	{
		$query = $query == 0 ? $query : $this->query;
		printf( "<pre>Query output:\n%s\n</pre>", $this->query );
	}

	public function fetch_assoc( $result )
	{
		return mssql_fetch_assoc( $result );
	}
	
	public function num_rows( $result )
	{
		return mssql_num_rows( $result );
	}
}
?>