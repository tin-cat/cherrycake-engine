<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Base class to represent system log events for the SystemLog module
 *
 * @package Cherrycake
 * @category Classes
 */
class SystemLogEvent extends Item {
	protected $tableName = "cherrycake_systemLog";
	protected $cacheSpecificPrefix = "SystemLog";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id"
		],
		"dateAdded" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
			"title" => "Date added"
		],
		"type" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Type"
		],
		"class" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Class"
		],
		"subType" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Subtype"
		],
		"ip" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP,
			"title" => "IP"
		],
		"httpHost" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Host"
		],
		"requestUri" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Uri"
		],
		"browserString" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Browser string"
		],
		"description" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Description"
		],
		"data" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_SERIALIZED,
			"title" => "Data"
		]
	];

	/**
	 * Loads the item when no loadMethod has been provided on construction. This is the usual way of creating LogEvent objects for logging
	 *
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function loadInline($data = false) {
		$this->type = substr(get_called_class(), strpos(get_called_class(), "\\")+1);
		$this->class = debug_backtrace()[2]["class"];

		if (isset($data["dateAdded"]))
			$this->dateAdded = $data["dateAdded"];
		else
			$this->dateAdded = time();

		if (isset($data["subType"]))
			$this->subType = $data["subType"];

		if (isset($data["ip"]))
			$this->ip = $data["ip"];
		else
			$this->ip = $this->getClientIp();

		if (isset($data["httpHost"]))
			$this->httpHost = $data["httpHost"];
		else
			$this->httpHost = $this->getHttpHost();

		if (isset($data["requestUri"]))
			$this->requestUri = $data["requestUri"];
		else
			$this->requestUri = $this->getRequestUri();

		if (isset($data["browserString"]))
			$this->browserString = $data["browserString"];
		else
			$this->browserString = $this->getClientBrowserString();

		if (isset($data["description"]))
			$this->description = $data["description"];

		if (isset($data["data"]))
			$this->data = $data["data"];

		return parent::loadInline();
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
	 * @return mixed The client's IP, or false if it wasn't available
	 */
	function getClientIp() {
		if (IS_CLI)
			return false;
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * @return mixed The host reported by the server, or false if it wasn't available
	 */
	function getHttpHost() {
		if (IS_CLI)
			return false;
		return $_SERVER["HTTP_HOST"];
	}

	/**
	 * @return mixed The URI reported by the server, or false if it wasn't available
	 */
	function getRequestUri() {
		if (IS_CLI)
			return false;
		return $_SERVER["REQUEST_URI"];
	}

	/**
	 * getClientBrowserString
	 *
	 * @return mixed The client's browserstring, or false if it wasn't available
	 */
	function getClientBrowserString() {
		if (IS_CLI)
			return false;
		return $_SERVER["HTTP_USER_AGENT"];
	}
}