<?php

namespace Cherrycake\Database;

/**
 * DatabaseResultMysql
 *
 * Database result based on MySQL
 *
 * @package Cherrycake
 * @category Classes
 */
class DatabaseResultMysql extends DatabaseResult {
	/**
	 * @var DatabaseResult $resultHandler The MySQL result handler
	 */
	protected DatabaseResult $resultHandler;

	/**
	 * @var mixed The result obtained from the resultHandler after calling resultHandler->get_result()
	 */
	private mixed $result;

	/**
	 * @var string $rowClassName Holds the name of the class that handles database results at row level.
	 */
	protected string $rowClassName = "DatabaseRowMysql";

	/**
	 * Initializes the result, receiving and storing the result handler optionally.
	 * @param mysqli_result|bool $resultHandler Optional MySQL result object
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 */
	function init(
		mysqli_result|bool $resultHandler = false,
		array $setup = [],
	) {
		parent::init($resultHandler, $setup);

		if ($resultHandler) {
			$this->resultHandler = $resultHandler;
			$this->retrieveResult();
			$this->freeResult();
		}
	}

	/**
	 * Retrieves the result from the database and stores it in $data in the form of a tridimensional array
	 */
	function retrieveResult() {
		if (get_class($this->resultHandler) == "mysqli_stmt")
			$this->result = $this->resultHandler->get_result();
		else
			$this->result = $this->resultHandler;
		if (isset($this->result->num_rows) && $this->result->num_rows > 0)
			$this->setData($this->result->fetch_all(MYSQLI_ASSOC));
	}

	/**
	 * Frees the database result.
	 */
	function freeResult() {
		if (isset($this->result->num_rows) && $this->result->num_rows > 0)
			$this->result->free();
	}

	/**
	 * Returns the number of rows in the result.
	 * @return int The number of rows in the result.
	 */
	function countRows(): int {
		return sizeof($this->data);
	}

	/**
	 * Checks whether there is at least one result.
	 * @return bool True if there is at least one result, false otherwise.
	 */
	function isAny(): bool {
		return $this->data ? true : false;
	}

	/**
	 * Returns the current row in the query results and advances to the next one.
	 * @return DatabaseRow A provider-specific DatabaseRowMysql object. False if no more rows.
	 */
	function getRow(): DatabaseRow {
		if (!$this->data)
			return false;

		$rowData = current($this->data);
		if ($rowData) {
			$row = $this->createDatabaseRowObject();
			$row->init($rowData, $this->setup);
			next($this->data);
			return $row;
		}
		return false;
	}

	/**
	 * Sets the row pointer to the beginning, so the next retrieved row will be the first.
	 */
	function reset() {
		reset($this->data);
	}

	/**
	 * Returns a list of the available keys in each row.
	 * @return array A list of the available keys in each row
	 */
	function getRowKeys(): array  {
		return array_keys($this->data[0]);
	}

	/**
	 * @return int the Id generated on the latest insert query
	 */
	function getInsertId(): int {
		return $this->resultHandler->insert_id;
	}
}
