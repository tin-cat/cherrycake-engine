<?php

/**
 * JanitorLogItem
 *
 * @package Movefy
 */

namespace Cherrycake;

/**
 * Class that represents janitor log item
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorLogItem extends \Cherrycake\Item {
	protected $tableName = "cherrycake_janitor_log";
	protected $cacheSpecificPrefix = "CherrycakeJanitorLog";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"executionDate" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
			"title" => "Execution date",
		],
		"executionSeconds" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_FLOAT,
			"title" => "Time spent",
			"multiplier" => 1000,
			"decimals" => 2,
			"postfix" => "ms",
			"humanizeMethodName" => "humanizeExecutionSeconds",
			"humanizePostMethodName" => "humanizePostExecutionSeconds",
		],
		"taskName" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
			"title" => "Task"
		],
		"resultCode" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Result code",
			"humanizeMethodName" => "humanizeResultCode"
		],
		"resultDescription" => [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_SERIALIZED,
			"title" => "Result"
		]
	];

	function humanizeExecutionSeconds($rawValue) {
		// If the execution time is less than a millisecond, return <1ms
		if ($rawValue < 0.001)
			return "<1ms";
		return null;
	}

	function humanizePostExecutionSeconds($r, $item) {
		// If the execution time is one second or more, tint it red for warning
		if ($item->executionSeconds >= 1)
			return "<span style=\"color: tomato;\">".$r."</span>";
		else
			return $r;
	}

	function humanizeResultCode($janitorLogItem) {
		return \Cherrycake\Janitor::getJanitorTaskReturnCodeDescription($janitorLogItem->resultCode);
	}
}