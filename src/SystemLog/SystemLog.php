<?php

namespace Cherrycake\SystemLog;

use Cherrycake\Engine;

/**
 * Stores system events in a persistent log as they occur, aimed at monitoring failures, warnings and notices that usually generated by core modules.
 */
class SystemLog extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"eventsToLog" => false, // An array of the SystemLogEvent class names to be stored in the log. The events not listed here will not be logged even if they're triggered. Set it to false to log all events.
		"tableName" => "cherrycake_systemLog", // The name of the table in the database where the log events will be stored.
		"cacheProviderName" => "engine", // The name of the cache provider to use.
		"databaseProviderName" => "main", // The name of the database provider to use.
		"cacheKeyUniqueId" => "QueuedSystemLogEvents", // The unique cache key to use when storing events into cache.
		"isQueueInCache" => true // Whether to store events in a buffer using cache for improved performance instead of storing them in the database straightaway.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Database",
		"Cache"
	];

	/**
	 * init
	 *
	 * Initializes the module and sets the PHP error level
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;
		return true;
	}

	/**
	 * Logs an event
	 *
	 * @param SystemLogEvent $systemLogEvent A SystemLogEvent object to log
	 * @return boolean Whether the event has been logged or not
	 */
	function event($systemLogEvent) {
		if ($this->getConfig("eventsToLog") && !in_array($systemLogEvent->type, $this->getConfig("eventsToLog")))
			return false;

		return
			$this->getConfig("isQueueInCache")
			?
			$this->queueEventInCache($systemLogEvent)
			:
			$this->store($systemLogEvent);
	}

	/**
	 * Stores the given SystemLogEvent into cache (queues it) in order to be later processed by JanitorTaskSystemLog
	 *
	 * @param SystemLogEvent $systemLogEvent The system log event to queue
	 * @return boolean Whether the system log event could be queued or not
	 */
	function queueEventInCache($systemLogEvent) {
		return Engine::e()->Cache->{$this->getConfig("cacheProviderName")}->queueRPush($this->getCacheKey(), $systemLogEvent);
	}

	/**
	 * Stores the cached system log events into the database, should be called periodically, normally via a JanitorTask
	 * @return array An array where the first key is a boolean indicating wether the opeartion went ok or not, and the second key is an optional hash array containing detailed information about the operation done.
	 */
	function commit() {
		$count = 0;
		while (true) {
			if (!$systemLogEvent = Engine::e()->Cache->{$this->getConfig("cacheProviderName")}->queueLPop($this->getCacheKey()))
				break;
			$this->store($systemLogEvent);
			$count ++;
		}

		return [
			true,
			[
				"numberOfFlushedItems" => $count
			]
		];
	}

	function store($systemLogEvent) {
		return $systemLogEvent->insert();
	}

	/**
	 * getCacheKey
	 *
	 * @return string The cache key to use when retrieveing and storing cache items
	 */
	function getCacheKey() {
		return Engine::e()->Cache->buildCacheKey([
			"uniqueId" => $this->getConfig("cacheKeyUniqueId")
		]);
	}

	/**
	 * Purges logs older than purgeLogsOlderThanSeconds
	 * @return array An array where the first element is a boolean indicating wether the operation went ok or not, and the second element is a description of what happened.
	 */
	function purge() {

		$baseTimestamp = time();

		$result = Engine::e()->Database->{$this->getConfig("databaseProviderName")}->prepareAndExecute(
			"select count(*) as numberOf from ".Engine::e()->SystemLog->getConfig("tableName")." where dateAdded < ?",
			[
				[
					"type" => \Cherrycake\Database\Database::TYPE_DATETIME,
					"value" => $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds")
				]
			]
		);

		if (!$result)
			return [
				false,
				"Could not query the database"
			];

		$row = $result->getRow();
		$numberOfLogEntriesToPurge = $row->getField("numberOf");

		if ($numberOfLogEntriesToPurge > 0) {
			$result = Engine::e()->Database->{$this->getConfig("databaseProviderName")}->prepareAndExecute(
				"delete from ".Engine::e()->SystemLog->getConfig("tableName")." where dateAdded < ?",
				[
					[
						"type" => \Cherrycake\Database\Database::TYPE_DATETIME,
						"value" => $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds")
					]
				]
			);

			if (!$result)
				return [
					false,
					"Could not delete log entries from the database"
				];
		}

		return [
			true,
			[
				"Log entries older than ".$this->getConfig("purgeLogsOlderThanSeconds")." seconds purged" => $numberOfLogEntriesToPurge
			]
		];
	}
}
