<?php

/**
 * Log
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Log
 *
 * A module that logs meaningful events.
 *
 * Configuration example for log.config.php:
 * <code>
 * $logConfig = [
 *      "databaseProviderName" => "main", // The name of the database provider where the log table is found
 *      "cacheProviderName" => "huge", // The name of the cache provider that will be used to temporally store events as they happen, to be later added to the database by the JanitorTaskLog
 *      "cacheKeyUniqueId" => "QueuedLogEvents", // The unique cache key to use when storing events into cache. Defaults to "QueuedLogEvents"
 *      "isQueueInCache" => true, // Whether to store the event into cache (queue it) in order to be later processed by JanitorTaskLog, or directly store it on the database. Defaults to true.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category AppModules
 */

class Log extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this module
	 */
	protected $config = [
		"databaseProviderName" => "main", // The name of the database provider where the log table is found
		"cacheProviderName" => "huge", // The name of the cache provider that will be used to temporally store events as they happen, to be later added to the database by the JanitorTaskLog
		"cacheKeyUniqueId" => "QueuedLogEvents", // The unique cache key to use when storing events into cache. Defaults to "QueuedEvents"
		"isQueueInCache" => true
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Errors",
		"Database",
		"Cache"
	];

	/**
	 * init
	 *
	 * Initializes the module and loads the base LogEvent class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;

		global $e;
		$e->loadCherrycakeModuleClass("Log", "LogEvent");

		return true;
	}

	/**
	 * logEvent
	 *
	 * Logs the given $logEvent
	 *
	 * @param LogEvent $logEvent The LogEvent object to log
	 * @return boolean Whether the event could be logged or not
	 */
	function logEvent($logEvent) {
		return
			$this->getConfig("isQueueInCache")
			?
			$this->queueEventInCache($logEvent)
			:
			$this->store($logEvent);
	}

	/**
	 * queueEventInCache
	 *
	 * Stores the given LogEvent into cache (queues it) in order to be later processed by JanitorTaskLog
	 *
	 * @param LogEvent $logEvent The event to queue
	 * @return boolean Whether the event could be queued or not
	 */
	function queueEventInCache($logEvent) {
		global $e;
		return $e->Cache->{$this->getConfig("cacheProviderName")}->rPush($this->getCacheKey(), $logEvent);
	}

	/**
	 * Stores the cached events into the database, should be called periodically, normally via a JanitorTask
	 * @return array A hash array with information items about the flushing
	 */
	function commit() {
		global $e;
		$count = 0;
		while (true) {
			if (!$logEvent = $e->Cache->{$this->getConfig("cacheProviderName")}->lPop($this->getCacheKey()))
				break;
			$this->store($logEvent);
			$count ++;
		}

		return [
			true,
			"numberOfFlushedItems" => $count
		];
	}

	/**
	 * Stores the given LogEvent on the database.
	 *
	 * @param $logEvent
	 * @return integer The log event id on the database, false if failed
	 */
	function store($logEvent) {
		return $logEvent->insert();
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
}