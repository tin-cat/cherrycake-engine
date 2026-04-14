<?php

namespace Cherrycake\Cache;

/**
 * Additional interface for all cache providers that additionally implement Pool functionalities.
 * Pools allow multiple values to be stored in a named pool. It's not possible to retrieve specific items from the pool, as the items are not identified by a key. It's only possible to get random items from it.
 */
interface CacheProviderInterfacePool {
	/**
	 * Stores a value in a cache pool
	 *
	 * @param string $poolName The name of the pool
	 * @param string $value The value
	 * @return bool Whether the value has been correctly stored. False otherwise
	 */
	function poolAdd($poolName, $value);

	/**
	 * Gets a random value from the pool and removes it
	 *
	 * @param string $poolName The name of the pool
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function poolPop($poolName);

	/**
	 * Checks whether a value is stored or not in the pool.
	 *
	 * @param string $poolName The name of the pool
	 * @param $value The value
	 * @return bool True if the value exists in the pool, false otherwise
	 */
	function isInPool($poolName, $value);

	/**
	 * @param string $poolName The name of the pool
	 * @return integer The number of elements in the pool
	 */
	function poolCount($poolName);
}
