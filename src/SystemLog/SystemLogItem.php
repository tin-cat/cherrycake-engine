<?php

namespace Cherrycake\SystemLog;

/**
 * Class that represents a system log item
 *
 * @package Cherrycake
 * @category Classes
 */
class SystemLogItem extends \Cherrycake\Item {
	protected $tableName = "cherrycake_SystemLog";
	protected $cacheSpecificPrefix = "CherrycakeSystemLog";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"dateAdded" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_DATETIME,
			"title" => "Date",
		],
		"class" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "Class"
		],
		"type" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "Type"
		],
		"subType" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "SubType"
		],
		"ip" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_IP,
			"title" => "IP"
		],
		"httpHost" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "Host"
		],
		"requestUri" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "Uri"
		],
		"browserString" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "BrowserString"
		],
		"description" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
			"title" => "Description"
		],
		"data" => [
			"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_SERIALIZED,
			"title" => "Data"
		]
	];
}
