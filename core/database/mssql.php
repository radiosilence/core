<?php
/* Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY James Cleveland "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland. */

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

class MSSQLConnectionError extends \Core\Error {}
class MSSQLSelectDBError extends \Core\Error {}
?>