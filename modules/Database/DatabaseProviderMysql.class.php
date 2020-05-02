<?php

/**
 * DatabaseProviderMysql
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * DatabaseProviderMysql
 *
 * Database provider based on MySQL, using mysqli PHP interface.
 * Requires PHP to be compiled with the native MySQLnd driver, which improves perfomance. See here: http://www.php.net/manual/es/book.mysqlnd.php
 *
 * @package Cherrycake
 * @category Classes
 */
class DatabaseProviderMysql extends DatabaseProvider {
	/**
	 * @var array Configuration about fieldtypes (\Cherrycake\DATABASE_FIELD_TYPE_*) for each implementation of DatabaseProvider
	 */
	protected $fieldTypes = [
		DATABASE_FIELD_TYPE_INTEGER => [
			"stmtBindParamType" => "i"
		],
		DATABASE_FIELD_TYPE_TINYINT => [
			"stmtBindParamType" => "i"
		],
		DATABASE_FIELD_TYPE_FLOAT => [
			"stmtBindParamType" => "d"
		],
		DATABASE_FIELD_TYPE_DATE => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_DATETIME => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_TIMESTAMP => [
			"stmtBindParamType" => "i"
		],
		DATABASE_FIELD_TYPE_TIME => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_YEAR => [
			"stmtBindParamType" => "i"
		],
		DATABASE_FIELD_TYPE_STRING => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_TEXT => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_BLOB => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_BOOLEAN =>  [
			"stmtBindParamType" => "i"
		],
		DATABASE_FIELD_TYPE_IP => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_SERIALIZED => [
			"stmtBindParamType" => "s"
		],
		DATABASE_FIELD_TYPE_COLOR => [
			"stmtBindParamType" => "s"
		]
	];

	/**
	 * @var MySQLConnection Holds the MySQL connection handler
	 */
	private $connectionHandler;

	/**
	 * @var string $resultClassName Holds the name of the class that handles MySQL results.
	 */
	protected $resultClassName = "DatabaseResultMysql";

	/**
	 * connect
	 *
	 * Connects to MySQL
	 * @return bool True if the connection has been established, false otherwise
	 */
	function connect() {
		$this->connectionHandler = new \mysqli(
			$this->getConfig("host"),
			$this->getConfig("user"),
			$this->getConfig("password"),
			$this->getConfig("database")
		);

		if (mysqli_connect_error()) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error ".mysqli_connect_errno()." connecting to MySQL (".mysqli_connect_error().")"]);
			return false;
		}

		if (!$this->connectionHandler->set_charset($this->getConfig("charset"))) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error ".mysqli_connect_errno()." setting MySQL charset ".$this->getConfig("charset")." (".mysqli_connect_error().")"]);
			return false;
		}

		$this->isConnected = true;

		return true;
	}

	/**
	 * disconnect
	 *
	 * Disconnect from the database provider if needed.
	 * @return bool True if the disconnection has been done, false otherwise
	 */
	function disconnect() {
		if (!$this->connectionHandler->close())
		{
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error ".mysqli_connect_errno()." connecting to MySQL (".mysqli_connect_error().")"]);
			return false;
		}
		$this->isConnected = false;

		return true;
	}

	/**
	 * query
	 *
	 * Performs a query to MySQL.
	 *
	 * @param string $sql The SQL query string
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResultMysql A provider-specific DatabaseResultMysql object if the query has been executed correctly, false otherwise.
	 */
	function query($sql, $setup = false) {
		$this->requireConnection();

		if (!$resultHandler = $this->connectionHandler->query($sql, MYSQLI_STORE_RESULT)) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error querying MySQL (".$this->connectionHandler->error.")"]);
			return false;
		}

		$result = $this->createDatabaseResultObject();
		$result->init($resultHandler, $setup);		
		return $result;
	}

	/**
	 * prepare
	 *
	 * Prepares a query so it can be later executed as a prepared query with the DatabaseProvider::execute method.
	 *
	 * @param string $sql The SQL statement to prepare to be queried to the database, where all the variables are replaced by question marks.
	 *
	 * @return array A hash array with the following keys:
	 *  - sql: The passed sql statement
	 *  - statement: A provider-specific statement object if the query has been prepared correctly, false otherwise.
	 */
	function prepare($sql) {;
		$this->requireConnection();
		if (!$statement = $this->connectionHandler->prepare($sql)) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error MySQL preparing statement (".$this->connectionHandler->error.") in sql \"".$sql."\""]);
			return false;
		}
		
		return [
			"sql" => $sql,
			"statement" => $statement
		];
	}

	/**
	 * execute
	 *
	 * Executes a previously prepared query with the given parameters.
	 *
	 * @param array $prepareResult The prepared result as returned by the prepare method
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Each array element has the following keys:
	 *
	 * * type: One of the prepared statement variable type consts, i.e.: \Cherrycake\DATABASE_FIELD_TYPE_*
	 * * value: The value to be used for this variable on the prepared statement
	 *
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 *
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function execute($prepareResult, $parameters, $setup = false) {
		if (is_array($parameters)) {
			$types = "";
			foreach ($parameters as $parameter)
				$types .= $this->fieldTypes[$parameter["type"]]["stmtBindParamType"];
			reset($parameters);

			$callUserFuncParametersArray[] = $types;

			foreach ($parameters as $parameter) {
				switch ($parameter["type"]) {
					case DATABASE_FIELD_TYPE_INTEGER:
					case DATABASE_FIELD_TYPE_TINYINT:
					case DATABASE_FIELD_TYPE_YEAR:
					case DATABASE_FIELD_TYPE_FLOAT:
					case DATABASE_FIELD_TYPE_TIMESTAMP:
					case DATABASE_FIELD_TYPE_STRING:
					case DATABASE_FIELD_TYPE_TEXT:
					case DATABASE_FIELD_TYPE_BLOB:
						$value = $parameter["value"];
						break;
					case DATABASE_FIELD_TYPE_BOOLEAN:
						$value = $parameter["value"] ? 1 : 0;
						break;
					case DATABASE_FIELD_TYPE_DATE:
						$value = date("Y-n-j", $parameter["value"]);
						break;
					case DATABASE_FIELD_TYPE_DATETIME:
						$value = date("Y-n-j H:i:s", $parameter["value"]);
						break;
					case DATABASE_FIELD_TYPE_TIME:
						$value = date("H:i:s", $parameter["value"]);
						break;
					case DATABASE_FIELD_TYPE_IP:
						$value = inet_pton($parameter["value"]);
						break;
					case DATABASE_FIELD_TYPE_SERIALIZED:
						$value = json_encode($parameter["value"], JSON_FORCE_OBJECT);
						break;
					case DATABASE_FIELD_TYPE_COLOR:
						$value = $parameter["value"]->getHex();
						break;
				}
				$callUserFuncParametersArray[] = $value;
			}
			reset($parameters);

			if (!call_user_func_array([$prepareResult["statement"], "bind_param"], $this->convertArrayValuesToRefForCallUserFuncArray($callUserFuncParametersArray))) {
				global $e;
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Error MySQL binding query statement parameters (".$prepareResult["statement"]->errno.": ".$prepareResult["statement"]->error.")",
					"errorVariables" => [
						"sql" => $prepareResult["sql"],
						"parameters" => "\"".implode("\" / \"", $callUserFuncParametersArray)."\""
					]
				]);
				return false;
			}
		}

		if (!$prepareResult["statement"]->execute()) {
			global $e;
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				"errorDescription" => "Error MySQL executing statement (".$prepareResult["statement"]->errno.": ".$prepareResult["statement"]->error.")",
				"errorVariables" => [
					"sql" => $prepareResult["sql"],
					"parameters" => "\"".implode("\" / \"", $callUserFuncParametersArray)."\""
				]
			]);
			return false;
		}

		$result = $this->createDatabaseResultObject();

		$result->init($prepareResult["statement"], $setup);
		$prepareResult["statement"]->free_result();

		return $result;
	}

	/**
	 * convertArrayValuesToRefForCallUserFuncArray
	 *
	 * From a given regular one-dimensional array, it returns the same array but with its values as references. Intended to be used to call bind_param method within a call_user_func_array function call.
	 *
	 * @param array $array The array to convert
	 * @return array The converted array
	 */
	function convertArrayValuesToRefForCallUserFuncArray($array) {
		if (strnatcmp(phpversion(),'5.3') >= 0) {
			$refs = [];
			foreach ($array as $key => $value)
				$refs[$key] = &$array[$key];
			return $refs;
		}
		return $array;
	}

	/**
	 * safeString
	 *
	 * Treats the given string in order to let it be safely included in an SQL sentence as a string literal.
	 *
	 * @param string $string The safe string
	 */
	function safeString($string) {
		$this->requireConnection();
		return $this->connectionHandler->real_escape_string($string);
	}
}