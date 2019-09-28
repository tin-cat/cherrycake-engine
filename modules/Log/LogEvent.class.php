<?php

/**
 * LogEvent
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * LogEvent
 *
 * Base class to represent log events for the Log module
 *
 * @package Cherrycake
 * @category Classes
 */
class LogEvent extends Item {
	protected $tableName = "log";
	protected $cacheSpecificPrefix = "Log";

	protected $fields = [
		"id" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER],
		"timestamp" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME],
		"type" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"subType" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING],
		"ip" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP],
		"user_id" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER],
		"outher_id" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER],
		"secondaryOuther_id" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER],
		"additionalData" => ["type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_SERIALIZED]
	];

	/**
	 * @var bool $isUseCurrentLoggedUserId Whether to use current logged user's id (if any) if no "userId" field had been given.
	 */
	protected $isUseCurrentLoggedUserId = false;

	/**
	 * @var bool $isUseCurrentClientIp Whether to use the client's ip if no other ip is specifiec. Defaults to true.
	 */
	protected $isUseCurrentClientIp = true;

	/**
	 * @var string $typeDescription The description of the log event type. Intended to be overloaded.
	 */
	private $typeDescription;

	/**
	 * @var string $outherIdDescription The description of the outher_id field contents for this log event type. Intended to be overloaded when needed.
	 */
	private $outherIdDescription;

	/**
	 * @var string $secondaryOutherIdDescription The description of the outher_id field contents for this log event type. Intended to be overloaded when needed.
	 */
	private $secondaryOutherIdDescription;

	/**
	 * Loads the item when no loadMethod has been provided on construction. This is the usual way of creating LogEvent objects for logging
	 *
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function loadInline($data = false) {
		$this->type = substr(get_called_class(), strpos(get_called_class(), "\\")+1);
		$this->subType = $data["subType"];

		if ($data["ip"])
			$this->ip = $data["ip"];
		else
		if ($this->isUseCurrentClientIp)
			$this->ip = $this->getClientIp();

		if ($data["userId"])
			$this->user_id = $data["userId"];
		else
		if ($this->isUseCurrentLoggedUserId) {
			global $e;
			$e->loadCherrycakeModule("Login");
			if ($e->Login && $e->Login->isLogged()) {
				$this->user_id = $e->Login->user->id;
			}
		}

		if ($data["timestamp"])
			$this->timestamp = $data["timestamp"];
		else
			$this->timestamp = time();
		
		if ($data["additionalData"])
			$this->additionalData = $data["additionalData"];

		return parent::loadInline($data);
	}

	/**
	 * getClientIp
	 *
	 * @return string The client's IP
	 */
	function getClientIp() {
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
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
	 * debug
	 *
	 * @return array An array containing debug information about this log event
	 */
	function getDebugInfo() {
		return [
			"type" => $this->type,
			"timestamp" => $this->timestamp,
			"typeDescription" => $this->typeDescription,
			"isUseCurrentClientIp" => $this->isUseCurrentClientIp,
			"isUseCurrentLoggedUserId" => $this->isUseCurrentLoggedUserId,
			"outherIdDescription" => $this->outherIdDescription,
			"secondaryOutherIdDescription" => $this->secondaryOutherIdDescription,
			"ip" => $this->ip,
			"userId" => $this->user_id,
			"outherId" => $this->outher_id,
			"secondaryOutherId" => $this->secondaryOuther_id,
			"additional" => $this->additionalData,
			"eventDescription" => $this->eventDescription
		];
	}
}