<?php

namespace Cherrycake\Stats;

use Cherrycake\Engine;

/**
 * Stores and manages statistical information
 */
class Stats extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"databaseProviderName" => "main", // The name of the database provider to use.
		"cacheProviderName" => "engine", // The name of the cache provider used to temporarily store stats events. Must support queueing.
		"cacheKeyUniqueId" => "QueuedStats", // The unique cache key to use when storing stat events into cache. Defaults to "QueuedStats"
		"isQueueInCache" => true // Whether to store stats events in a buffer using cache for improved performance instead of storing them in the database straightaway.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Database",
		"Cache"
	];

	/**
	 * Triggers a stats event.
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
		return Engine::e()->Cache->{$this->getConfig("cacheProviderName")}->queueRPush($this->getCacheKey(), $statsEvent);
	}

	/**
	 * @return string The cache key to use when retrieveing and storing cache items
	 */
	function getCacheKey() {
		return Engine::e()->Cache->buildCacheKey([
			"uniqueId" => $this->getConfig("cacheKeyUniqueId")
		]);
	}

	/**
	 * Stores the cached StatsEvents into the database, should be called periodically, normally via a JanitorTask
	 * @return array An array where the first key is a boolean indicating wether the opeartion went ok or not, and the second key is an optional hash array containing detailed information about the operation done.
	 */
	function commit() {
		$count = 0;
		while (true) {
			if (!$statsEvent = Engine::e()->Cache->{$this->getConfig("cacheProviderName")}->queueLPop($this->getCacheKey()))
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
