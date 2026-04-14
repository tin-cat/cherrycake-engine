<?php

namespace Cherrycake\Database;

/**
 * Class that represents a row retrieved from a query to a database, specific for MySql
 */
class DatabaseRowMysql extends DatabaseRow {
	/**
	 * Returns a treated version of the given data according to the given \Cherrycake\Database\Database::TYPE_* fieldType. $data contains data as is came out from the database.
	 * @param mixed $data The data to treat, as it came out of the database
	 * @param integer $fieldType The field type, one of \Cherrycake\Database\Database::TYPE_*
	 * @return mixed The treated data
	 */
	function treatFieldData(
		mixed $data,
		int $fieldType,
	): mixed {
		switch ($fieldType) {
			case \Cherrycake\Database\Database::TYPE_INTEGER:
			case \Cherrycake\Database\Database::TYPE_TINYINT:
			case \Cherrycake\Database\Database::TYPE_YEAR:
			case \Cherrycake\Database\Database::TYPE_FLOAT:
			case \Cherrycake\Database\Database::TYPE_STRING:
			case \Cherrycake\Database\Database::TYPE_BLOB:
				return $data;
				break;
			case \Cherrycake\Database\Database::TYPE_BOOLEAN:
				return $data ? true : false;
				break;
			case \Cherrycake\Database\Database::TYPE_DATE:
			case \Cherrycake\Database\Database::TYPE_DATETIME:
			case \Cherrycake\Database\Database::TYPE_TIME:
			case \Cherrycake\Database\Database::TYPE_TIMESTAMP:
				return strtotime($data);
				break;
			case \Cherrycake\Database\Database::TYPE_IP:
				return $data ? long2ip($data) : false;
				break;
			case \Cherrycake\Database\Database::TYPE_SERIALIZED:
				return json_decode($data, true);
				break;
			case \Cherrycake\Database\Database::TYPE_COLOR:
				return $data ? new Color("withHex", $data) : false;
				break;
			default:
				return $data;
		}
	}
}
