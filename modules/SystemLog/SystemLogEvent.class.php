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
		"id" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER],
		"dateAdded" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME],
		"type" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"class" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"subType" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"ip" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP],
		"httpHost" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"requestUri" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"browserString" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"description" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"data" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_SERIALIZED]
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

		if ($data["dateAdded"])
			$this->dateAdded = $data["dateAdded"];
		else
			$this->dateAdded = mktime();

		if ($data["subType"])
			$this->subType = $data["subType"];

		if ($data["ip"])
			$this->ip = $data["ip"];
		else
			$this->ip = $this->getClientIp();

		if ($data["httpHost"])
			$this->httpHost = $data["httpHost"];
		else
			$this->httpHost = $this->getHttpHost();

		if ($data["requestUri"])
			$this->requestUri = $data["requestUri"];
		else
			$this->requestUri = $this->getRequestUri();

		if ($data["browserString"])
			$this->browserString = $data["browserString"];
		else
			$this->browserString = $this->getClientBrowserString();

		if ($data["description"])
			$this->description = $data["description"];

		if ($data["data"])
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
	 * @return string The client's IP
	 */
	function getClientIp() {
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * @return string The host reported by the server
	 */
	function getHttpHost() {
		return $_SERVER["HTTP_HOST"];
	}

	/**
	 * @return string The URI reported by the server
	 */
	function getRequestUri() {
		return $_SERVER["REQUEST_URI"];
	}

	/**
	 * getClientBrowserString
	 *
	 * @return string The client's browserstring
	 */
	function getClientBrowserString() {
		return $_SERVER["HTTP_USER_AGENT"];
	}
}