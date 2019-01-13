<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const STATS_EVENT_TIME_RESOLUTION_MINUTE = 0;
const STATS_EVENT_TIME_RESOLUTION_HOUR = 1;
const STATS_EVENT_TIME_RESOLUTION_DAY = 2;
const STATS_EVENT_TIME_RESOLUTION_MONTH = 3;
const STATS_EVENT_TIME_RESOLUTION_YEAR = 4;

/**
 * Stores and manages statistical information
 *
 * Configuration example for stats.config.php:
 * <code>
 * $statsConfig = [
 *	"databaseProviderName" => "main", // The name of the database provider.
 *	"cacheProviderName" => "huge", // The name of the cache provider used to temporarily store stats events. Must support queueing.
 *	"cacheKeyUniqueId" => "QueuedStats", // The unique cache key to use when storing stat events into cache. Defaults to "QueuedStats"
 *	"isQueueInCache" => true, // Whether to store the stats events into cache (queue it) in order to be later processed by JanitorTaskStats, or directly store it on the database. Defaults to true.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Stats extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"databaseProviderName" => "main", // The name of the database provider.
		"cacheProviderName" => "huge", // The name of the cache provider used to temporarily store stats events. Must support queueing.
		"cacheKeyUniqueId" => "QueuedStats", // The unique cache key to use when storing stat events into cache. Defaults to "QueuedStats"
		"isQueueInCache" => true // Whether to store the stats events into cache (queue it) in order to be later processed by JanitorTaskStats, or directly store it on the database. Defaults to true.
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
	 * Initializes the module
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		global $e;
		$e->loadCherrycakeModuleClass("Stats", "StatsEvent");

		return true;
	}

	/**
	 * Triggers an stats event. This is the method that should be called whenever a statistical event happens.
	 * @param StatsEvent $statsEvent The StatsEvent object to trigger
	 * @return boolean True if everything went ok, false otherwise
	 */
	function trigger($statsEvent) {
		return
			$this->getConfig("isQueueInCache")
			?
			$this->queueEventInCache($statsEvent)
			:
			$statsEvent->store();
	}

	/**
	 * Stores the given StatsEvent into cache for later processing via JanitorTaskStats by calling the flushCache method
	 * @param StatsEvent $statsEvent The StatsEvent object to store into cache
	 * @return boolean True if everything went ok, false otherwise
	 */
	function queueEventInCache($statsEvent) {
		global $e;
		return $e->Cache->{$this->getConfig("cacheProviderName")}->rPush($this->getCacheKey(), $statsEvent);
	}

	/**
	 * @return string The cache key to use when retrieveing and storing cache items
	 */
	function getCacheKey() {
		global $e;
		return $e->Cache->buildCacheKey([
			"uniqueId" => $this->getConfig("cacheKeyUniqueId")
		]);
	}

	/**
	 * Stores the cached StatsEvents into the database, should be called periodically, normally via a JanitorTask
	 * @return array An array where the first key is a boolean indicating wether the opeartion went ok or not, and the second key is an optional hash array containing detailed information about the operation done.
	 */
	function commit() {
		global $e;
		$count = 0;
		while (true) {
			if (!$statsEvent = $e->Cache->{$this->getConfig("cacheProviderName")}->lPop($this->getCacheKey()))
				break;
			$statsEvent->store();
			$count ++;
		}

		return [
			true,
			[
				"numberOfFlushedItems" => $count
			]
		];
	}
}