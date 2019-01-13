<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class to work with user items
 *
 * @package Cherrycake
 * @category Classes
 */
class SystemLogItems extends \Cherrycake\Items {
	protected $tableName = "cherrycake_systemLog";
	protected $itemClassName = "\Cherrycake\SystemLogItem";
	protected $isCache = false;

	/**
	 * Finds system log items with the specified filters
	 * @param array $p A hash array of parameters, with the possible keys from \Cherrycake\Items::get plus the following possible keys:
	 *
	 * * fromDate: <timestamp|false> Default: false
	 * * toDate: <timestamp|false> Default: false
	 * * order: An array of orders to apply, amongst the following ones:
	 * 	- chronological: Most revent events first
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"fromDate" => ["default" => false],
			"toDate" => ["default" => false],
			"order" => ["default" => ["chronological"]],
			"orders" => ["addArrayKeysIfNotExist" => [
				"chronological" => $this->tableName.".dateAdded desc, ".$this->tableName.".id desc"
			]]
		]);

		// Build query wheres
		if ($p["fromDate"])
			$p["wheres"][] = [
				"sqlPart" => "dateAdded > ?",
				"values" => [
					[
						"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["fromDate"]
					]
				]
			];

		if ($p["toDate"])
			$p["wheres"][] = [
				"sqlPart" => "dateAdded <= ?",
				"values" => [
					[
						"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["toDate"]
					]
				]
			];

		return parent::fillFromParameters($p);
	}
}