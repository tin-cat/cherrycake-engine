<?php

namespace Cherrycake\Modules\SystemLog;

use Cherrycake\Classes\Engine;

/**
 * Base class to represent system log events for the SystemLog module
 */
class SystemLogEvent extends \Cherrycake\Classes\Item {
	static public $tableName = "cherrycake_systemLog";
	static protected $cacheSpecificPrefix = "SystemLog";

	static protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
			"title" => "Id"
		],
		"dateAdded" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
			"title" => "Date added",
			"defaultValue" => \Cherrycake\Modules\Database\Database::DEFAULT_VALUE_DATETIME
		],
		"type" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Type"
		],
		"class" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Class"
		],
		"subType" => [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
			"title" => "Subtype"
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
			"title" => "Browser string"
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

	/**
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	static function build($data = false): SystemLogEvent {
		$systemLogEvent = new SystemLogEvent;

		$systemLogEvent->type = get_called_class();
		$systemLogEvent->class = debug_backtrace()[2]["class"];

		if (isset($data["dateAdded"]))
			$systemLogEvent->dateAdded = $data["dateAdded"];
		else
			$systemLogEvent->dateAdded = time();

		if (isset($data["subType"]))
			$systemLogEvent->subType = $data["subType"];

		if (isset($data["ip"]))
			$systemLogEvent->ip = $data["ip"];
		else
			$systemLogEvent->ip = $systemLogEvent->getClientIp();

		if (isset($data["httpHost"]))
			$systemLogEvent->httpHost = $data["httpHost"];
		else
			$systemLogEvent->httpHost = $systemLogEvent->getHttpHost();

		if (isset($data["requestUri"]))
			$systemLogEvent->requestUri = $data["requestUri"];
		else
			$systemLogEvent->requestUri = $systemLogEvent->getRequestUri();

		if (isset($data["browserString"]))
			$systemLogEvent->browserString = $data["browserString"];
		else
			$systemLogEvent->browserString = $systemLogEvent->getClientBrowserString();

		if (isset($data["description"]))
			$systemLogEvent->description = $data["description"];

		if (isset($data["data"]))
			$systemLogEvent->data = $data["data"];

		return $systemLogEvent;
	}

	/**
	 * getEventDescription
	 *
	 * Intended to be overloaded.
	 *
	 * @return string A detailed description of the currently loaded event
	 */
	function getEventDescription() {
	}

	/**
	 * @return mixed The client's IP, or an empty string if it wasn't available
	 */
	function getClientIp(): string {
		if (Engine::e()->isCli())
			return '';
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * @return mixed The host reported by the server, or false if it wasn't available
	 */
	function getHttpHost() {
		if (Engine::e()->isCli())
			return false;
		return $_SERVER["HTTP_HOST"];
	}

	/**
	 * @return mixed The URI reported by the server, or false if it wasn't available
	 */
	function getRequestUri() {
		if (Engine::e()->isCli())
			return false;
		return $_SERVER["REQUEST_URI"];
	}

	/**
	 * getClientBrowserString
	 *
	 * @return mixed The client's browserstring, or false if it wasn't available
	 */
	function getClientBrowserString() {
		if (Engine::e()->isCli())
			return false;
		return $_SERVER["HTTP_USER_AGENT"];
	}
}
