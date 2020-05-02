<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class to work with janitorlog items
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorLogItems extends \Cherrycake\Items {
	protected $tableName = "cherrycake_janitor_log";
	protected $itemClassName = "\Cherrycake\JanitorLogItem";
	protected $isCache = false;

	/**
	 * Finds janitor log items with the specified filters
	 * @param array $p A hash array of parameters, with the possible keys from \Cherrycake\Items::get plus the following possible keys:
	 *
	 * * fromExecutionDate: <timestamp|false> Default: false
	 * * toExecutionDate: <timestamp|false> Default: false
	 * * order: An array of orders to apply, amongst the following ones:
	 * 	- chronological: Most revent events first
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"fromExecutionDate" => ["default" => false],
			"toExecutionDate" => ["default" => false],
			"order" => ["default" => ["chronological"]],
			"orders" => ["addArrayKeysIfNotExist" => [
				"chronological" => $this->tableName.".executionDate desc, ".$this->tableName.".id desc"
			]]
		]);

		// Build query wheres
		if ($p["fromExecutionDate"])
			$p["wheres"][] = [
				"sqlPart" => "executionDate > ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["fromExecutionDate"]
					]
				]
			];

		if ($p["toExecutionDate"])
			$p["wheres"][] = [
				"sqlPart" => "executionDate <= ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["toExecutionDate"]
					]
				]
			];

		return parent::fillFromParameters($p);
	}
}