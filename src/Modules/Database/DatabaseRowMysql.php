<?php

namespace Cherrycake\Modules\Database;

use Exception;
use Throwable;

/**
 * Class that represents a row retrieved from a query to a database, specific for MySql
 */
class DatabaseRowMysql extends DatabaseRow {
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
		switch ($fieldType) {
			case \Cherrycake\Modules\Database\Database::TYPE_INTEGER:
			case \Cherrycake\Modules\Database\Database::TYPE_TINYINT:
			case \Cherrycake\Modules\Database\Database::TYPE_YEAR:
			case \Cherrycake\Modules\Database\Database::TYPE_FLOAT:
			case \Cherrycake\Modules\Database\Database::TYPE_STRING:
			case \Cherrycake\Modules\Database\Database::TYPE_BLOB:
			case \Cherrycake\Modules\Database\Database::TYPE_UUID4:
				return $data;
				break;
			case \Cherrycake\Modules\Database\Database::TYPE_BOOLEAN:
				return $data ? true : false;
				break;
			case \Cherrycake\Modules\Database\Database::TYPE_DATE:
			case \Cherrycake\Modules\Database\Database::TYPE_DATETIME:
			case \Cherrycake\Modules\Database\Database::TYPE_TIME:
			case \Cherrycake\Modules\Database\Database::TYPE_TIMESTAMP:
				return strtotime($data);
				break;
			case \Cherrycake\Modules\Database\Database::TYPE_IP:
				return $data ? long2ip($data) : false;
				break;
			case \Cherrycake\Modules\Database\Database::TYPE_SERIALIZED:
			case \Cherrycake\Modules\Database\Database::TYPE_OBJECT:
				try {
					return unserialize($data);
				} catch (Throwable $e) {
					echo "Exception: ".$e->getMessage()."<br>";
					echo $data."<hr>";
					return null;
				}
				break;
			case \Cherrycake\Modules\Database\Database::TYPE_COLOR:
				return $data ? new Color("withHex", $data) : false;
				break;
			default:
				return $data;
		}
	}
}
