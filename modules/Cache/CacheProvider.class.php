<?php

/**
 * CacheProvider
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

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

/**
 * Additional interface for all cache providers that additionally implement Queueing functionalities
 * Queues act as FIFO or LIFO queues, you can add items to the end or to the begginning of a queue, and you can only get items from the beggining or from the end of the queue. When an item is read from the queue, it is also removed from it.
 */
interface CacheProviderInterfaceQueue {
	/**
	 * Puts a value to the end of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function rPush($queueName, $value);

	/**
	 * Puts a value to the beggining of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function lPush($queueName, $value);

	/**
	 * Returns the element at the end of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value
	 */
	function rPop($queueName);

	/**
	 * Returns the element at the beggining of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value
	 */
	function lPop($queueName);
}

/**
 * Additional interface for all cache providers that additionally implement Hashed lists functionalities
 * Hashed lists allow you to store sets of items identified by a specific key, into a hash list identified also by its own key. You can then get or remove specific items from the list, or remove all items of a list at the same time.
 */
interface CacheProviderInterfaceHash {
	/**
	 * Adds an item with the given key with the given value to the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param mixed $value The value
	 * @return integer 1 if the key wasn't on the hash list and it was added. 0 if the key already existed and it was updated.
	 */
	function hSet($listName, $key, $value);

	/**
	 * Retrieves the stored value at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return mixed The stored value
	 */
	function hGet($listName, $key);

	/**
	 * Removes the item at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 */
	function hDel($listName, $key);

	/**
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return boolean Whether the item at the given key exists on the specified listName
	 */
	function hExists($listName, $key);

	/**
	 * @param string $listName The name of the hashed list
	 * @return integer The number of items stored at the given listName
	 */
	function hLen($listName);

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array of all the items on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function hGetAll($listName);

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array containing all the keys on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function hGetKeys($listName);

	/**
	 * Increments the number stored at the given key in the given listName by the given increment
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param integer $increment The amount to increment
	 * @return integer The value after applying the increment
	 */
	function hIncrBy($listName, $key, $increment = 1);
}

/**
 * CacheProvider
 *
 * Base class for cache provider implementations. Intended to be overloaded by a higher level cache system implementation class.
 * Cache providers are only connected when required (when the first request is received)
 *
 * @package Cherrycake
 * @category Classes
 */
class CacheProvider {
	/**
	 * @var array $config Default configuration options
	 */
	protected $config = [];

	/**
	 * @var bool $isConnected Whether this cache is connected to the provider, when needed
	 */
	protected $isConnected = false;

	/**
	 * config
	 *
	 * Sets the configuration of the cache provider.
	 *
	 * @param array $config The cache provider parameters
	 */
	function config($config) {
		$this->config = $config;
	}

	/**
	 * getConfig
	 *
	 * Gets a configuration value
	 *
	 * @param string $key The configuration key
	 */
	function getConfig($key) {
		return isset($this->config[$key]) ? $this->config[$key] : false;
	}

	/**
	 * connect
	 *
	 * Connects to the cache provider. Intended to be overloaded by a higher level cache system implementation class.
	 */
	function connect() {
	}

	/**
	 * disconnect
	 *
	 * Disconnects from the cache provider if needed.
	 * @return bool True if the disconnection has been done, false otherwise
	 */
	function disconnect() {
	}

	/**
	 * requireConnection
	 *
	 * Calls the connect method in case this provider is not yet connected
	 */
	function requireConnection() {
		if (!$this->isConnected)
			$this->Connect();
	}

	/**
	 * Returns a string representation of the value passed to be stored on the cache
	 * @param  mixed $value The value to serialize
	 * @return string The serialized value
	 */
	function serialize($value) {
		return serialize($value);
	}

	/**
	 * Unserializes the given value
	 * @param string $value The value to unserialize
	 * @return mixed The unserialized value
	 */
	function unserialize($value) {
		return unserialize($value);
	}
}