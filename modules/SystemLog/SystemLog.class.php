<?php

/**
 * SystemLog
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * SystemLog
 *
 * Stores system's log
 *
 * Configuration example for systemlog.config.php:
 * <code>
 * $systemLogConfig = [
 *	"errorsToLog" => [ // An array of strings of the class names corresponding to the system log event types that must be logged
 *		"SystemLogEvenInfo",
 *		"SystemLogEvenWarning",
 *		"SystemLogEvenError",
 *		"SystemLogEvenCritical",
 *		"SystemLogEvenHack"
 *	 ],
 *	"databaseProviderName" => "main", // The name of the database provider where the system log table is found
 *	"tableName" => "cherrycake_systemLog" // The name of the table, defaulted to this
 *	"cacheProviderName" => "huge", // The name of the cache provider that will be used to temporally store log events as they happen, to be later added to the database by the JanitorTaskSystemLog
 *  "cacheKeyUniqueId" => "QueuedSystemLogEvents", // The unique cache key to use when storing events into cache. Defaults to "QueuedSystemLogEvents"
 *  "isQueueInCache" => true, // Whether to store the log events into cache (queue it) in order to be later processed by JanitorTaskSystemLog, or directly store it on the database. Defaults to true.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class SystemLog extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"errorsToLog" => [
			"SystemLogEventInfo",
			"SystemLogEventWarning",
			"SystemLogEventError",
			"SystemLogEventCritical",
			"SystemLogEventHack"
		],
		"tableName" => "cherrycake_systemLog",
		"cacheKeyUniqueId" => "QueuedSystemLogEvents", // The unique cache key to use when storing events into cache. Defaults to "QueuedSystemLogEvents"
		"isQueueInCache" => true
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
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
	function init() {
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
		if (!in_array($systemLogEvent->type, $this->getConfig("errorsToLog")))
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
		global $e;
		return $e->Cache->{$this->getConfig("cacheProviderName")}->rPush($this->getCacheKey(), $systemLogEvent);
	}

	/**
	 * Stores the cached system log events into the database, should be called periodically, normally via a JanitorTask
	 * @return array An array where the first key is a boolean indicating wether the opeartion went ok or not, and the second key is an optional hash array containing detailed information about the operation done.
	 */
	function commit() {
		global $e;
		$count = 0;
		while (true) {
			if (!$systemLogEvent = $e->Cache->{$this->getConfig("cacheProviderName")}->lPop($this->getCacheKey()))
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
		global $e;
		return $e->Cache->buildCacheKey([
			"uniqueId" => $this->getConfig("cacheKeyUniqueId")
		]);
	}

	/**
	 * Purges logs older than purgeLogsOlderThanSeconds
	 * @return array An array where the first element is a boolean indicating wether the operation went ok or not, and the second element is a description of what happened.
	 */
	function purge() {
		global $e;

		$baseTimestamp = time();

		$result = $e->Database->{$this->getConfig("databaseProviderName")}->prepareAndExecute(
			"select count(*) as numberOf from ".$e->SystemLog->getConfig("tableName")." where dateAdded < ?",
			[
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
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
			$result = $e->Database->{$this->getConfig("databaseProviderName")}->prepareAndExecute(
				"delete from ".$e->SystemLog->getConfig("tableName")." where dateAdded < ?",
				[
					[
						"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
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

	/**
	 * Get the log in HTML format
	 * This method expects that the engine has been loaded with the following modules:
	 * * Database
	 * * Ui
	 * * UiComponentTable
	 * 
	 * @param array $setup Setup parameters
	 * @return string The HTML
	 */
	function getLogHtml($setup = false) {
		global $e;

		$janitorLogItems = new \Cherrycake\SystemLogItems([
			"fillMethod" => "fromParameters",
			"isForceNoCache" => true,
			"p" => [
				"limit" => 100
			]
		]);

		return \Cherrycake\UiComponentTable::build([
			"items" => $janitorLogItems,
			"itemFields" => [
				"id" => [],
				"dateAdded" => [],
				"class" => [],
				"type" => [],
				"subType" => [],
				"ip" => [],
				"httpHost" => [],
				"requestUri" => [],
				"browserString" => [],
				"description" => [],
				"data" => []
			],
			"additionalCssClasses" => "fullWidth"
		])->buildHtml();
	}
}