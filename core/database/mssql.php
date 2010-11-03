<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Extends mysqli to do interesting
 * things, should be useful for prepared
 * statements, etc.
 *
 * @package database
 * @subpackage core
 */

namespace Core\Database;

import('core.dependency');
import('core.containment');

\Core\DEPENDENCY::require_functions(array(
	'mssql_connect', 'mssql_query'
));

class MSSQL {
	
	private $connection = 0;
	public static $total_queries = 0;
	public static $total_time = 0;
	public $database = 0;
	public $query;

	public function __construct($hostname, $username, $password, $database) {
		$this->connection = mssql_connect($hostname, $username, $password);

		if(!$this->connection) {
			throw new MSSQLConnectionError("Could not connect to database host [" . $hostname . "].");
		}
		if(!mssql_select_db($database, $this->connection)) {
			throw new MSSQLSelectDBError("Could not select database [" . $database . "].");
		}

		$this->database = $database;
		return 1;
	}

	public function query($query) {

		$result = mssql_query($query, $this->connection);

		DB_MSSQL::$total_time += $time_end - $time_start;

		return $result;
	}

	public function build_query() {
		$this->query = new query($this);
		return $this->query;
	}

	public function run_query(&$result) {

		if($result = $this->query($this->query)) {
			unset($this->query);
			return 1;
		}
		else {
			$this->print_query();
			trigger_error('Query failure.' . $this->error, E_USER_ERROR);
			unset($this->query);
			return 0;
		}

	}

	public function real_escape_string($string) {
		# Not secure in any way ever.
		if(is_numeric($data))
		{
			return $data;
		}
		$unpacked = unpack('H*hex', $data);
		return '0x' . $unpacked['hex'];
	}

	public function fetch_assoc($result) {
		return mssql_fetch_assoc($result);
	}
	
	public function num_rows($result) {
		return mssql_num_rows($result);
	}
}

class MSSQLContainer extends \Core\ConfiguredContainer {

    /**
     * Get a living MSSQL object.
     */
    public function get_mssql() {
        import('core.database.mssql');
        $this->load_config();
        $this->check_config();
    }
}

class MSSQLConnectionError extends \Core\Error {}
class MSSQLSelectDBError extends \Core\Error {}
?>
