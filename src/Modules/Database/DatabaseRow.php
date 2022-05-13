<?php

namespace Cherrycake\Modules\Database;

use Cherrycake\Engine;

/**
 * Class that represents a row retrieved from a query to a database
 */
class DatabaseRow {
	/**
	 * @var array A bidimensional hash array containing the row data
	 */
	protected $data;

	/**
	 * @var array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 */
	protected $setup;

	/**
	 * Initializes the row, stablishing the data.
	 * @param array $data A hash array containing the row data
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 */
	function init(
		array $data,
		array $setup = [],
	) {
		$this->setup = $setup;
		$this->setData($data);
	}

	/**
	 * Returns a hash array of all the data. If $fields is specified, the returned data is treated accordingly.
	 * @param array $fields An optional array definition of fields and their types
	 * @return array A hash array of all the data on this DatabaseRow
	 */
	function getData(
		array $fields = [],
	): array {
		if (!$fields)
			return $this->data;

		foreach ($this->data as $key => $value)
			$data[$key] = $fields[$key]["type"] ?? false ? $this->treatFieldData($this->data[$key], $fields[$key]["type"]) : $value;
		reset($this->data);
		return $data;
	}

	/**
	 * Sets the data for this row. Can be overloaded, i.e., in order to take into account any $this->setup configuration that affect the field values
	 * @param array $data A bidimensional hash array containing the row data
	 */
	function setData(array $data) {
		foreach ($data as $key => $value) {
			if (isset($this->setup["timestampFieldNames"]) && is_array($this->setup["timestampFieldNames"])) {
				if (in_array($key, $this->setup["timestampFieldNames"]))
					$value = strtotime($value);
			}
			$this->data[$key] = $value;
		}
	}

	/**
	 * Returns the value of the specified field on the row. If $fields is specified, the returned data is treated accordingly.
	 * @param string $key The key of the field
	 * @param array $fields An optional array definition of fields and their types
	 * @return mixed The value of the field, or null if the field didn't exist
	 */
	function getField(
		string $key,
		array $fields = [],
	): mixed {
		if ($fields && $fields[$key]["type"]) {
			if ($fields[$key]["isMultiLanguage"]) {
				return $this->treatFieldData($this->data["key"].Engine::e()->Locale->getLanguage(), $fields[$key]["type"]);
			}
			else
				return $this->treatFieldData($this->data["key"], $fields[$key]["type"]);
		}
		else
			return $this->data[$key] ?? null;
	}

	/**
	 * Returns a treated version of the given data according to the given \Cherrycake\Modules\Database\Database::TYPE_* fieldType. $data contains data as is came out from the database.
	 * @param mixed $data The data to treat, as it came out of the database
	 * @param integer $fieldType The field type, one of \Cherrycake\Modules\Database\Database::TYPE_*
	 * @return mixed The treated data
	 */
	function treatFieldData(
		mixed $data,
		int $fieldType,
	): mixed {
		return $data;
	}
}
