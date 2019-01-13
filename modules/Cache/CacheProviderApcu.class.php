<?php

/**
 * CacheProviderApcu
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * CacheProviderApcu
 *
 * Cache Provider based on APCu. It provides a very fast memory caching but limited to a relatively small amount of cached objects, depending on memory available on the APCu server configuration.
 *
 * @package Cherrycake
 * @category Classes
 */
class CacheProviderApcu extends CacheProvider implements CacheProviderInterface {
	/**
	 * @var bool $isConnected Always set to true, since APCu cache doesn't requires an explicit connection
	 */
	protected $isConnected = true;

	/**
	 * set
	 *
	 * Stores a value in the APCu cache.
	 *
	 * @param string $key The identifier key
	 * @param mixed $value The value
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function set($key, $value, $ttl = false) {
		return apcu_store($key, $value, $ttl);
	}

	/**
	 * get
	 *
	 * Gets a value from the APCu cache.
	 *
	 * @param string $key The identifier key
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function get($key) {
		return apcu_fetch($key);
	}

	/**
	 * delete
	 *
	 * Deletes a value from the APCu cache.
	 *
	 * @param string $key The identifier key for the object to be deleted
	 */
	function delete($key) {
		return apcu_delete($key);
	}

	/**
	 * increment
	 *
	 * Increments a number stored in the cache by the given step
	 *
	 * @param string $key The identified key for the object to be incremented
	 * @param integer $step The amount to increment the value, defaults to 1
	 * @return mixed The current value stored in the cache key, or false if error
	 */
	function increment($key, $step = 1) {
		return apcu_inc($key, $step);
	}

	/**
	 * append
	 *
	 * Appends the given value to the existent value in the cache. The key must already exists, or error will be returned.
	 *
	 * This method is not particularly optimal for APCu, since APCu does not provides a method to append content to an existing cached item.
	 *
	 * @param string $key The item key to append the given value to
	 * @param string $value The string to append
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function append($key, $value, $ttl = false) {
		return $this->set($key, $this->get($key).$value, $ttl);
	}

	/**
	 * isKey
	 *
	 * Checks whether a value is stored or not in the APCu cache.
	 *
	 * @param $key The identifier key
	 * @return bool True if the value exists in the cache, false otherwise
	 */
	function isKey($key) {
		return apcu_exists($key);
	}

	/**
	 * touch
	 *
	 * Stablishes a new expiration TTL for an element in the APCu cache. Since APCu doesn't provides a touch method itself, a workaround is done, which is probably slow.
	 *
	 * @param string $key The identifier key for the object to be touched
	 * @param integer $ttl The new TTL (Time To Live) for the stored value in seconds
	 */
	function touch($key, $ttl) {
		$this->set($key, $this->get($key), $ttl);
	}
}