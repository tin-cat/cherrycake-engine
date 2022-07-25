<?php

namespace Cherrycake\Modules\Log;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Database\Database;

/**
 * Base class to represent log events for the Log module
 */
class LogEvent extends \Cherrycake\Classes\Item {
	static public $tableName = 'log';
	static protected $cacheSpecificPrefix = 'Log';

	static protected $fields = [
		'id' => ['type' => Database::TYPE_INTEGER],
		'timestamp' => ['type' => Database::TYPE_DATETIME],
		'type' => ['type' => Database::TYPE_STRING],
		'subType' => ['type' => Database::TYPE_STRING],
		'ip' => ['type' => Database::TYPE_IP],
		'user_id' => ['type' => Database::TYPE_INTEGER],
		'outher_id' => ['type' => Database::TYPE_INTEGER],
		'secondaryOuther_id' => ['type' => Database::TYPE_INTEGER],
		'additionalData' => ['type' => Database::TYPE_SERIALIZED]
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
	protected $typeDescription;

	/**
	 * @var string $outherIdDescription The description of the outher_id field contents for this log event type. Intended to be overloaded when needed.
	 */
	protected $outherIdDescription;

	/**
	 * @var string $secondaryOutherIdDescription The description of the secondaryOuther_id field contents for this log event type. Intended to be overloaded when needed.
	 */
	protected $secondaryOutherIdDescription;

	/*
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function __construct($data = false) {
		$this->type = get_called_class();
		$this->subType = $data["subType"] ?? false;

		if ($data["ip"] ?? false)
			$this->ip = $data["ip"];
		else
		if ($this->isUseCurrentClientIp)
			$this->ip = $this->getClientIp();

		if ($data["userId"] ?? false)
			$this->user_id = $data["userId"];
		else
		if ($this->isUseCurrentLoggedUserId) {
			Engine::e()->loadCoreModule("Login");
			if (Engine::e()->Login && Engine::e()->Login->isLogged()) {
				$this->user_id = Engine::e()->Login->user->id;
			}
		}

		if ($data["timestamp"] ?? false)
			$this->timestamp = $data["timestamp"];
		else
			$this->timestamp = time();

		if (isset($data["outher_id"]))
			$this->outher_id = $data["outher_id"];

		if (isset($data["secondaryOuther_id"]))
			$this->secondaryOuther_id = $data["secondaryOuther_id"];

		if (isset($data["additionalData"]))
			$this->additionalData = $data["additionalData"];

		return parent::__construct();
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
