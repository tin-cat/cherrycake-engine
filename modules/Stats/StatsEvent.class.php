<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Base class to represent a stats event for the Stats module
 *
 * @package Cherrycake
 * @category Classes
 */
class StatsEvent extends Item {
	protected $tableName = "cherrycake_stats";
	protected $cacheSpecificPrefix = "Stats";

	protected $fields = [
		"id" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER],
		"type" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING],
		"subType" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING],
		"resolution" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER],
		"timestamp" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME],
		"secondary_id" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER],
		"tertiary_id" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER],
		"count" => ["type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER]
	];

	/**
	 * The time frame resolution used by this event. One of the available STATS_EVENT_TIME_RESOLUTION_? constants.
	 * @var timeResolution
	 */
	protected $timeResolution = \Cherrycake\STATS_EVENT_TIME_RESOLUTION_DAY;

	/**
	 * @var string $typeDescription The description of the log event type. Intended to be overloaded.
	 */
	protected $typeDescription;

	/**
	 * @var boolean Whether this StatsEvent uses secondary id or not.
	 */
	protected $isSecondaryId = false;

	/**
	 * @var boolean Whether this StatsEvent uses tertiary id or not.
	 */
	protected $isTertiaryId = false;

	/**
	 * @var string $secondaryIdDescription The description of the secondary_id field contents for this stats event type. Intended to be overloaded when needed.
	 */
	protected $secondaryIdDescription = false;

	/**
	 * @var string $tertiaryIdDescription The description of the tertiary_id field contents for this stats event type. Intended to be overloaded when needed.
	 */
	protected $tertiaryIdDescription = false;

	/**
	 * Loads the item when no loadMethod has been provided on construction. This is the usual way of creating LogEvent objects for logging
	 *
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function loadInline($data = false) {
		global $e;
		$this->type = get_called_class();
		$this->subType = $data["subType"] ?? null;
		$this->timestamp = isset($data["timestamp"]) ? $data["timestamp"] : time();
		$this->resolution = $this->timeResolution;
		return parent::loadInline($data);
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
			"secondary_id" => $this->secondary_id,
			"tertiary_id" => $this->tertiary_id,
			"count" => $this->count
		];
	}

	/**
	 * Returns the timestamp that is used on the database to group events by their time resolution based on the timestamp field. For example, an event that took place on 2016-7-12 18:04:24, if the stats events has a daily time resolution, would be converted to 2016-7-12 00:00:00. If the time resolution were to be hourly, the resulting timestamp would be 2016-7-12 12:00:00. If the time resolution were to be monthly, the resulting timestamp would be 2016-7-1 00:00:00
	 * @param integer $timestamp The timestamp to convert. If left to false, it uses this StatsEvent's timestamp
	 * @return integer The resulting timestamp
	 */
	function getTimestampForTimeResolution($timestamp = false) {
		if (!$timestamp)
			$timestamp = $this->timestamp;
		switch ($this->timeResolution) {
			case \Cherrycake\STATS_EVENT_TIME_RESOLUTION_MINUTE:
				$paramsSetup = [true, true, false, true, true, true];
				break;
			case \Cherrycake\STATS_EVENT_TIME_RESOLUTION_HOUR:
				$paramsSetup = [true, false, false, true, true, true];
				break;
			case \Cherrycake\STATS_EVENT_TIME_RESOLUTION_DAY:
				$paramsSetup = [false, false, false, true, true, true];
				break;
			case \Cherrycake\STATS_EVENT_TIME_RESOLUTION_MONTH:
				$paramsSetup = [false, false, false, true, false, true];
				break;
			case \Cherrycake\STATS_EVENT_TIME_RESOLUTION_YEAR:
				$paramsSetup = [false, false, false, false, false, true];
				break;
		}
		$paramsMktimeLetters = "HisnjY";
		$paramsValuesWhenFalse = "000111";
		foreach ($paramsSetup as $index => $paramSetup)
			$params[] = $paramSetup ? date($paramsMktimeLetters[$index], $timestamp) : strval($paramsValuesWhenFalse[$index]);
		return call_user_func_array("mktime", $params);
	}

	/**
	 * Overload the magic set method to set the data $key to the given $value when timestamp is specified in order to perform the time resolution adjust
	 * @param string $key The key of the data to set
	 * @param mixed $value The value
	 */
	function __set($key, $value) {
		if ($key == "timestamp")
			$value = $this->getTimestampForTimeResolution($value);
		return parent::__set($key, $value);
	}

	/**
	 * Updates the stats database with this event's data. This method should be called only once per StatsEvent, and it should usally only be called by Stats->flushCache or by Stats->trigger
	 * @return boolean True if everything went ok, false otherwise
	 */
	function store() {
		global $e;

		// Check if this event is already on the database
		
		// Prepare query parameters
		// type
		$parameters[] = [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
			"value" => $this->type
		];

		// subType
		if ($this->subType)
			$parameters[] = [
				"type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
				"value" => $this->subType
			];

		// resolution
		$parameters[] = [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
			"value" => $this->resolution
		];
		// timestamp
		$parameters[] = [
			"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
			"value" => $this->timestamp
		];
		// secondary_id
		if ($this->isSecondaryId)
			$parameters[] = [
				"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
				"value" => $this->secondary_id
			];
		// tertiary_id
		if ($this->isTertiaryId)
			$parameters[] = [
				"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
				"value" => $this->tertiary_id
			];

		if (!$result = $e->Database->{$this->databaseProviderName}->prepareAndExecute(
			$sql = "
				select
					".$this->tableName.".*
				from
					".$this->tableName."
				where
					".$this->tableName.".type = ?
				and
					".$this->tableName.".subType ".($this->subType ? " = ? " : " is null ")."
				and
					".$this->tableName.".resolution = ?
				and
					".$this->tableName.".timestamp = ?
				".($this->isSecondaryId ? " and ".$this->tableName.".secondary_id = ? " : "")."
				".($this->isTertiaryId ? " and ".$this->tableName.".tertiary_id = ? " : "")."
			",
			$parameters
		))
			return false;

		if ($result->isAny()) {
			// Stats event already exists on database, update it
			$statsEventClassName = get_called_class();
			$statsEvent = new $statsEventClassName([
				"loadMethod" => "fromDatabaseRow",
				"databaseRow" => $result->getRow()
			]);
			$statsEvent->update([
				"count" => $statsEvent->count + 1
			]);
		}
		else {
			// Stats event doesn't exists on database, create a new one
			$this->count = 1;
			return $this->insert();
		}
	}
}