<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class to work with log items
 *
 * @package Cherrycake
 * @category Classes
 */
class LogItems extends \Cherrycake\Items {
	protected $tableName = "log";
	protected $itemClassName = "\Cherrycake\LogItem";
	protected $isCache = false;

	/**
	 * Finds janitor log items with the specified filters
	 * @param array $p A hash array of parameters, with the possible keys from \Cherrycake\Items::get plus the following possible keys:
	 *
	 * * fromTimestamp: <timestamp|false> Default: false
	 * * toTimestamp: <timestamp|false> Default: false
	 * * onlyByUsersWithPermission: <false|permission name> Retrieve log items only by users with the specified permission name. Default: false
	 * * retrieveUsername: <true|false> Whether to retrieve the user name or not. Default: false
	 * * order: An array of orders to apply, amongst the following ones:
	 * 	- chronological: Most revent events first
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"type" => ["default" => false],
			"subType" => ["default" => false],
			"fromTimestamp" => ["default" => false],
			"toTimestamp" => ["default" => false],
			"onlyByUsersWithPermission" => ["default" => false],
			"retrieveUsername" => ["default" => false],
			"order" => ["default" => ["chronological"]],
			"orders" => ["addArrayKeysIfNotExist" => [
				"chronological" => $this->tableName.".timestamp desc, ".$this->tableName.".id desc"
			]]
		]);

		// Build query wheres
		if ($p["type"])
			$p["wheres"][] = [
				"sqlPart" => "type = ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
						"value" => $p["type"]
					]
				]
			];

		if ($p["subType"])
			$p["wheres"][] = [
				"sqlPart" => "subType = ?",
				"values" => [
					[
						"subType" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
						"value" => $p["subType"]
					]
				]
			];

		if ($p["fromTimestamp"])
			$p["wheres"][] = [
				"sqlPart" => "timestamp > ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["fromTimestamp"]
					]
				]
			];

		if ($p["toTimestamp"])
			$p["wheres"][] = [
				"sqlPart" => "timestamp <= ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["toTimestamp"]
					]
				]
			];

		if ($p["onlyByUsersWithPermission"]) {
			$p["tables"][] = "users";
			$p["wheres"][]= [
				"sqlPart" => "log.user_id = users.id"
			];
			$p["wheres"][]= [
				"sqlPart" => "users.isPermission".$p["onlyByUsersWithPermission"]." = 1"
			];
		}

		if ($p["retrieveUsername"]) {
			$p["selects"][] = "users.username as username";
			$p["tables"][] = "users";
			$p["wheres"][]= [
				"sqlPart" => "log.user_id = users.id"
			];
		}

		return parent::fillFromParameters($p);
	}
}