<?php

namespace Cherrycake\Modules\SystemLog;

/**
 * Class that represents a system log item
 */
class SystemLogItem extends \Cherrycake\Classes\Item {
	protected $tableName = "cherrycake_SystemLog";
	protected $cacheSpecificPrefix = "CherrycakeSystemLog";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"dateAdded" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
			"title" => "Date",
		],
		"class" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Class"
		],
		"type" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Type"
		],
		"subType" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "SubType"
		],
		"ip" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_IP,
			"title" => "IP"
		],
		"httpHost" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Host"
		],
		"requestUri" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Uri"
		],
		"browserString" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "BrowserString"
		],
		"description" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Description"
		],
		"data" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_SERIALIZED,
			"title" => "Data"
		]
	];
}
