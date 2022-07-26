<?php

namespace Cherrycake\Modules\Database;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Errors\Errors;

/**
 * Database provider based on MySQL, using mysqli PHP interface.
 * Requires PHP to be compiled with the native MySQLnd driver, which improves perfomance. See here: http://www.php.net/manual/es/book.mysqlnd.php
 */
class DatabaseProviderMysql extends DatabaseProvider {
	/**
	 * @var array Configuration about fieldtypes (\Cherrycake\Modules\Database\Database::TYPE_*) for each implementation of DatabaseProvider
	 */
	protected $fieldTypes = [
		\Cherrycake\Modules\Database\Database::TYPE_INTEGER => [
			"stmtBindParamType" => "i"
		],
		\Cherrycake\Modules\Database\Database::TYPE_TINYINT => [
			"stmtBindParamType" => "i"
		],
		\Cherrycake\Modules\Database\Database::TYPE_FLOAT => [
			"stmtBindParamType" => "d"
		],
		\Cherrycake\Modules\Database\Database::TYPE_DATE => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_DATETIME => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_TIMESTAMP => [
			"stmtBindParamType" => "i"
		],
		\Cherrycake\Modules\Database\Database::TYPE_TIME => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_YEAR => [
			"stmtBindParamType" => "i"
		],
		\Cherrycake\Modules\Database\Database::TYPE_STRING => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_TEXT => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_BLOB => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_BOOLEAN =>  [
			"stmtBindParamType" => "i"
		],
		\Cherrycake\Modules\Database\Database::TYPE_IP => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_SERIALIZED => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_OBJECT => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_COLOR => [
			"stmtBindParamType" => "s"
		],
		\Cherrycake\Modules\Database\Database::TYPE_UUID4 => [
			"stmtBindParamType" => "s"
		],
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
	 * Connects to MySQL
	 * @return bool True if the connection has been established, false otherwise
	 */
	function connect(): bool {
		$this->connectionHandler = new \mysqli(
			$this->getConfig("host"),
			$this->getConfig("user"),
			$this->getConfig("password"),
			$this->getConfig("database")
		);

		if (mysqli_connect_error()) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error ".mysqli_connect_errno()." connecting to MySQL (".mysqli_connect_error().")"
			);
			return false;
		}

		if (!$this->connectionHandler->set_charset($this->getConfig("charset"))) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error ".mysqli_connect_errno()." setting MySQL charset ".$this->getConfig("charset")." (".mysqli_connect_error().")"
			);
			return false;
		}

		$this->isConnected = true;

		return true;
	}

	/**
	 * Disconnect from the database provider if needed.
	 * @return bool True if the disconnection has been done, false otherwise
	 */
	function disconnect(): bool {
		if (!$this->connectionHandler->close())
		{
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error ".mysqli_connect_errno()." connecting to MySQL (".mysqli_connect_error().")"
			);
			return false;
		}
		$this->isConnected = false;

		return true;
	}

	/**
	 * Performs a query to MySQL.
	 * @param string $sql The SQL query string
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResultMysql A provider-specific DatabaseResultMysql object if the query has been executed correctly, false otherwise.
	 */
	function query(
		string $sql,
		array $setup = [],
	): DatabaseResultMysql {
		$this->requireConnection();

		if (!$resultHandler = $this->connectionHandler->query($sql, MYSQLI_STORE_RESULT)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error querying MySQL (".$this->connectionHandler->error.")"
			);
			return false;
		}

		$result = $this->createDatabaseResultObject();
		$result->init($resultHandler, $setup);
		return $result;
	}

	/**
	 * Prepares a query so it can be later executed as a prepared query with the DatabaseProvider::execute method.
	 * @param string $sql The SQL statement to prepare to be queried to the database, where all the variables are replaced by question marks.
	 * @return array A hash array with the following keys:
	 *  - sql: The passed sql statement
	 *  - statement: A provider-specific statement object if the query has been prepared correctly, false otherwise.
	 */
	function prepare(string $sql): array {
		$this->requireConnection();
		if (!$statement = $this->connectionHandler->prepare($sql)) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error MySQL preparing statement (".$this->connectionHandler->error.") in sql \"".$sql."\""
			);
			return false;
		}

		return [
			"sql" => $sql,
			"statement" => $statement
		];
	}

	/**
	 * Executes a previously prepared query with the given parameters.
	 * @param array $prepareResult The prepared result as returned by the prepare method
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Each array element has the following keys:
	 * * type: One of the prepared statement variable type consts, i.e.: \Cherrycake\Modules\Database\Database::TYPE_*
	 * * value: The value to be used for this variable on the prepared statement
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function execute(
		array $prepareResult,
		array $parameters,
		array $setup = [],
	): DatabaseResult {
		if (is_array($parameters)) {
			$types = "";
			foreach ($parameters as $parameter)
				$types .= $this->fieldTypes[$parameter["type"]]["stmtBindParamType"];
			reset($parameters);

			foreach ($parameters as $parameter) {
				switch ($parameter["type"]) {
					case \Cherrycake\Modules\Database\Database::TYPE_INTEGER:
					case \Cherrycake\Modules\Database\Database::TYPE_TINYINT:
					case \Cherrycake\Modules\Database\Database::TYPE_YEAR:
					case \Cherrycake\Modules\Database\Database::TYPE_FLOAT:
					case \Cherrycake\Modules\Database\Database::TYPE_TIMESTAMP:
					case \Cherrycake\Modules\Database\Database::TYPE_STRING:
					case \Cherrycake\Modules\Database\Database::TYPE_TEXT:
					case \Cherrycake\Modules\Database\Database::TYPE_BLOB:
					case \Cherrycake\Modules\Database\Database::TYPE_UUID4:
						$value = $parameter["value"];
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_BOOLEAN:
						$value = $parameter["value"] ? 1 : 0;
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_DATE:
						$value = date("Y-n-j", $parameter["value"]);
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_DATETIME:
						$value = date("Y-n-j H:i:s", $parameter["value"]);
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_TIME:
						$value = date("H:i:s", $parameter["value"]);
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_IP:
						$value = ip2long($parameter["value"]);
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_SERIALIZED:
					case \Cherrycake\Modules\Database\Database::TYPE_OBJECT:
						$value = serialize($parameter["value"]);
						break;
					case \Cherrycake\Modules\Database\Database::TYPE_COLOR:
						$value = $parameter["value"]->getHex();
						break;
				}
				$values[] = $value;
			}
			reset($parameters);

			if ($values ?? false)
				$prepareResult["statement"]->bind_param($types, ...$values);
		}

		if (!$prepareResult["statement"]->execute()) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "Error MySQL executing statement (".$prepareResult["statement"]->errno.": ".$prepareResult["statement"]->error.")",
				variables: [
					"sql" => $prepareResult["sql"],
					"values" => "\"".implode("\" / \"", $values)."\""
				]
			);
			return false;
		}

		$result = $this->createDatabaseResultObject();

		$result->init($prepareResult["statement"], $setup);
		$prepareResult["statement"]->free_result();

		return $result;
	}

	/**
	 * From a given regular one-dimensional array, it returns the same array but with its values as references. Intended to be used to call bind_param method within a call_user_func_array function call.
	 * @param array $array The array to convert
	 * @return array The converted array
	 */
	function convertArrayValuesToRefForCallUserFuncArray(array $array): array {
		if (strnatcmp(phpversion(),'5.3') >= 0) {
			$refs = [];
			foreach ($array as $key => $value)
				$refs[$key] = &$array[$key];
			return $refs;
		}
		return $array;
	}

	/**
	 * Treats the given string in order to let it be safely included in an SQL sentence as a string literal.
	 * @param string $string The safe string
	 * @return string The safe string
	 */
	function safeString($string): string {
		$this->requireConnection();
		return $this->connectionHandler->real_escape_string($string);
	}
}
