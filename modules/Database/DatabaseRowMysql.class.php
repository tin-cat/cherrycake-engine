<?php

/**
 * DatabaseRowMysql
 *
 * @package Cherrycake
 */

namespace Cherrycake;

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
	 * Returns a treated version of the given data according to the given \Cherrycake\DATABASE_FIELD_TYPE_* fieldType. $data contains data as is came out from the database.
	 * @param mixed $data The data to treat, as it came out of the database
	 * @param integer $fieldType The field type, one of \Cherrycake\DATABASE_FIELD_TYPE_*
	 * @return mixed The treated data
	 */
	function treatFieldData($data, $fieldType) {
		switch ($fieldType) {
			case \Cherrycake\DATABASE_FIELD_TYPE_INTEGER:
			case \Cherrycake\DATABASE_FIELD_TYPE_TINYINT:
			case \Cherrycake\DATABASE_FIELD_TYPE_YEAR:
			case \Cherrycake\DATABASE_FIELD_TYPE_FLOAT:
			case \Cherrycake\DATABASE_FIELD_TYPE_STRING:
			case \Cherrycake\DATABASE_FIELD_TYPE_BLOB:
				return $data;
				break;
			case \Cherrycake\DATABASE_FIELD_TYPE_BOOLEAN:
				return $data ? true : false;
				break;
			case \Cherrycake\DATABASE_FIELD_TYPE_DATE:
			case \Cherrycake\DATABASE_FIELD_TYPE_DATETIME:
			case \Cherrycake\DATABASE_FIELD_TYPE_TIME:
			case \Cherrycake\DATABASE_FIELD_TYPE_TIMESTAMP:
				return strtotime($data);
				break;
			case \Cherrycake\DATABASE_FIELD_TYPE_IP:
				return $data ? inet_ntop($data) : false;
				break;
			case \Cherrycake\DATABASE_FIELD_TYPE_SERIALIZED:
				return json_decode($data, true);
				break;
			case \Cherrycake\DATABASE_FIELD_TYPE_COLOR:
				return $data ? new Color("withHex", $data) : false;
				break;
			default:
				return $data;
		}
	}
}