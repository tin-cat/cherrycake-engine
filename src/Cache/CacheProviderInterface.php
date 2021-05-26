<?php

namespace Cherrycake\Cache;

/**
 * Interface for all cache providers
 */
interface CacheProviderInterface {
	/**
	 * Stores a value in cache.
	 *
	 * @param string $key The identifier key
	 * @param mixed $value The value
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Whether the value has been correctly stored. False otherwise
	 */
	function set($key, $value, $ttl = false);

	/**
	 * Gets a value from the cache.
	 *
	 * @param string $key The identifier key
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function get($key);

	/**
	 * Deletes a value from the cache.
	 *
	 * @param string $key The identifier key for the object to be deleted
	 * @return bool True if the object could be deleted. False otherwise
	 */
	function delete($key);

	/**
	 * Increments a number stored in the cache by the given step
	 *
	 * @param string $key The identified key for the object to be incremented
	 * @param integer $step The amount to increment the value, defaults to 1
	 * @return mixed The current value stored in the cache key, or false if error
	 */
	function increment($key, $step = 1);

	/**
	 * Appends the given value to the existent value in the cache. The key must already exists, or error will be returned.
	 *
	 * @param string $key The item key to append the given value to
	 * @param string $value The string to append
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function append($key, $value, $ttl = false);

	/**
	 * Checks whether a value is stored or not in the cache.
	 *
	 * @param $key The identifier key
	 * @return bool True if the value exists in the cache, false otherwise
	 */
	function isKey($key);

	/**
	 * Stablishes a new expiration TTL for an element in the cache.
	 *
	 * @param string $key The identifier key for the object to be touched
	 * @param integer $ttl The new TTL (Time To Live) for the stored value in seconds
	 */
	function touch($key, $ttl);
}
