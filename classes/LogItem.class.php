<?php

/**
 * LogItem
 *
 * @package Movefy
 */

namespace Cherrycake;

/**
 * Class that represents a log item
 *
 * @package Cherrycake
 * @category Classes
 */
class LogItem extends \Cherrycake\Item {
	protected $tableName = "log";
	protected $cacheSpecificPrefix = "CherrycakeLog";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"timestamp" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIMESTAMP,
			"title" => "Timestamp",
		],
		"type" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Type"
		],
		"subType" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Sub type"
		],
		"ip" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP,
			"title" => "IP"
		],
		"user_id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "User id"
		],
		"username" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "User name"
		]
	];

	function humanizeExecutionSeconds($rawValue) {
		// If the execution time is less than a millisecond, return <1ms
		if ($rawValue < 0.001)
			return "<1ms";
		return null;
	}

	function humanizePostExecutionSeconds($r, $rawValue) {
		// If the execution time is one second or more, tint it red for warning
		if ($rawValue >= 1)
			return "<span style=\"color: tomato;\">".$r."</span>";
		else
			return $r;
	}

	function humanizeResultCode($resultCode) {
		global $e;
		return $e->Janitor->getJanitorTaskReturnCodeDescription($resultCode);
	}
}