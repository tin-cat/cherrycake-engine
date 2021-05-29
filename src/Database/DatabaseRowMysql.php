<?php

namespace Cherrycake\Database;

/**
 * DatabaseRow
 *
 * Class that represents a row retrieved from a query to a database, specific for MySql
 *
 * @package Cherrycake
 * @category Classes
 */
class DatabaseRowMysql extends DatabaseRow {
	/**
	 * Returns a treated version of the given data according to the given \Cherrycake\Database\DATABASE_FIELD_TYPE_* fieldType. $data contains data as is came out from the database.
	 * @param mixed $data The data to treat, as it came out of the database
	 * @param integer $fieldType The field type, one of \Cherrycake\Database\DATABASE_FIELD_TYPE_*
	 * @return mixed The treated data
	 */
	function treatFieldData($data, $fieldType) {
		switch ($fieldType) {
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_INTEGER:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_TINYINT:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_YEAR:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_FLOAT:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_BLOB:
				return $data;
				break;
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_BOOLEAN:
				return $data ? true : false;
				break;
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_DATE:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_DATETIME:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_TIME:
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_TIMESTAMP:
				return strtotime($data);
				break;
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_IP:
				return $data ? long2ip($data) : false;
				break;
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_SERIALIZED:
				return json_decode($data, true);
				break;
			case \Cherrycake\Database\DATABASE_FIELD_TYPE_COLOR:
				return $data ? new Color("withHex", $data) : false;
				break;
			default:
				return $data;
		}
	}
}
