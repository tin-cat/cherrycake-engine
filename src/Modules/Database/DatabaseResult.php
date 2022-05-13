<?php

namespace Cherrycake\Modules\Database;

/**
 * Base class for database result implementations. Intended to be overloaded by a higher level database system implementation class.
 */
class DatabaseResult {
	/**
	 * @var array A tridimensional hash array containing the result data
	 */
	protected array $data = [];

	/**
	 * @var array $setup Optional array with additional options
	 */
	protected array $setup = [];

	/**
	 * @var string $rowClassName Holds the name of the class that handles database results at row level. Must be set by an overloaded class
	 */
	protected string $rowClassName;

	/**
	 * Creates a database provider-dependant DatabaseRow type object and returns it.
	 * @return DatabaseRow The higher-level DatabaseRow object type
	 */
	function createDatabaseRowObject(): DatabaseRow {
		eval("\$result = new \\Cherrycake\\Modules\\Database\\".$this->rowClassName."();");
		return $result;
	}

	/**
	 * Initializes the result, receiving and storing the result handler. Intended to be overloaded by a higher level provider-specific DatabaseResult class if needed
	 * @param mixed $resultHandler Optional MySQL result object
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 */
	function init(
		mixed $resultHandler = false,
		array $setup = [],
	) {
		$this->setup = $setup;
	}

	/**
	 * Retrieves the result from the database and stores it in $data in the form of a tridimensional array, false if no results. Must be overloaded by a higher level provider-specific DatabaseResult class
	 */
	function retrieveResult() {}

	/**
	 * Returns all the data
	 * @return array The data in the form of a tridimensional array.
	 */
	function getData(): array {
		return $this->data;
	}

	/**
	 * Sets the data to the specified one
	 * @param array $data The data to set in the form of a tridimensional array.
	 */
	function setData(array $data) {
		$this->data = $data;
		reset($this->data);
	}

	/**
	 * Frees the database result. Must be overloaded by a higher level provider-specific DatabaseResult class
	 */
	function freeResult() {}

	/**
	 * Returns the number of rows in the result. Must be overloaded by a provider-specific DatabaseResult class
	 * @return int The number of rows in the result.
	 */
	function countRows(): int {
	}

	/**
	 * Checks whether there is at least one result. Must be overloaded by a provider-specific DatabaseResult class
	 * @return bool True if there is at least one result, false otherwise.
	 */
	function isAny(): bool {}

	/**
	 * Returns the next row in the query results and advances to the next one. Must be overloaded by a provider-specific DatabaseResult class
	 * @return DatabaseRow|bool A provider-specific DatabaseRowMysql object. False if no more rows.
	 */
	function getRow(): DatabaseRow|bool {}

	/**
	 * Sets the row pointer to the beginning, so the next retrieved row will be the first. Must be overloaded by a provider-specific DatabaseResult class
	 */
	function reset() {}

	/**
	 * Returns a list of the available keys in each row. Must be overloaded by a provider-specific DatabaseResult class
	 * @return array A list of the available keys in each row
	 */
	function getRowKeys(): array {
	}

	/**
	 * @return int the Id generated on the latest insert query
	 */
	function getInsertId(): int {}
}
