<?php

/**
 * CacheProviderRedis
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * CacheProviderRedis
 *
 * Cache Provider based on Redis. It provides a relatively fast memory caching (slower than APC, though), but normally allows huge amounts of objects and big objects to be stored.
 *
 * @package Cherrycake
 * @category Classes
 */
class CacheProviderRedis extends CacheProvider implements CacheProviderInterface, CacheProviderInterfacePool, CacheProviderInterfaceQueue, CacheProviderInterfaceHash {
	/**
	 * @var array $config Holds the configuration of the cache provider when needed; things like host, port and password. This is set by the Cache object from the Cherrycake configuration files when this cache provider is setup.
	 */
	protected $config = [
		"scheme" => "tcp",
		"host" => "localhost",
		"port" => 6379,
		"database" => 0,
		"prefix" => "",
		"isPersistentConnection" => true
	];

	/**
	 * @var $client Holds the Predis client object
	 */
	private $client;

	/**
	 * connect
	 *
	 * Connects to the cache provider if needed. Intended to be overloaded by a higher level cache system implementation class.
	 */
	function connect() {
		require_once ENGINE_DIR."/vendor/autoload.php";
		\Predis\Autoloader::register();

		$this->client = new \Predis\Client(
			[
				"scheme" => $this->getConfig("scheme"),
				"host"  => $this->getConfig("host"),
				"port" => $this->getConfig("port"),
				"database" => $this->getConfig("database"),
				"persistent" => $this->getConfig("isPersistentConnection")
			],
			[
				"prefix" => $this->getConfig("prefix")
			]
		);
		
		if (!$this->client) {
			global $e;
			$e->Errors->Trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Error connecting to Redis"]);
			return false;
		}

		$this->isConnected = true;
	}

	/**
	 * disconnect
	 *
	 * Disconnects from the cache provider if needed.
	 * @return bool True if the disconnection has been done, false otherwise
	 */
	function disconnect() {
		if (!$this->GetConfig("isPersistentConnection")) {
			if (!$this->client->close()) {
				global $e;
				$e->Errors->Trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Error disconnecting from Redis"]);
				return false;
			}
		}
		else
			return true;
	}

	/**
	 * set
	 *
	 * Stores a value in the redis cache.
	 *
	 * @param string $key The identifier key
	 * @param mixed $value The value
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function set($key, $value, $ttl = false) {
		$this->RequireConnection();
		$r = $this->client->set($key, !$value ? $value : $this->serialize($value));
		if ($ttl > 0)
			$this->client->expire($key, $ttl);
		return $r;
	}

	/**
	 * get
	 *
	 * Gets a value from the cache.
	 *
	 * @param string $key The identifier key
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function get($key) {
		$this->RequireConnection();
		$r = $this->client->get($key);
		if (is_null($r))
			return false;
		return !$r ? $r : $this->unserialize($r, true);
	}

	/**
	 * delete
	 *
	 * Deletes a value from the cache.
	 *
	 * @param string $key The identifier key for the object to be deleted
	 * @return bool True if the object could be deleted. False otherwise
	 */
	function delete($key) {
		$this->RequireConnection();
		return $this->client->del($key);
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
		$this->RequireConnection();
		return $this->client->incrby($key, $step);
	}

	/**
	 * append
	 *
	 * Appends the given value to the existent value in the cache. The key must already exists, or error will be returned.
	 *
	 * @param string $key The item key to append the given value to
	 * @param string $value The string to append
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function append($key, $value, $ttl = false) {
		$this->RequireConnection();
		return $this->set($key, $this->get($key).$value, $ttl);
	}

	/**
	 * isKey
	 *
	 * Checks whether a value is stored or not in the redis cache. Since redis hasn't an specific method to check for the existence of an object, a workaround is done here (probably slow)
	 *
	 * @param $key The identifier key
	 * @return bool True if the value exists in the cache, false otherwise
	 */
	function isKey($key) {
		$this->RequireConnection();
		return $this->client->exists($key);
	}

	/**
	 * touch
	 *
	 * Stablishes a new expiration TTL for an element in the cache.
	 *
	 * @param string $key The identifier key for the object to be touched
	 * @param integer $ttl The new TTL (Time To Live) for the stored value in seconds
	 */
	function touch($key, $ttl) {
		$this->RequireConnection();
		$this->client->expire($key, $ttl);
	}

	/**
	 * Puts a value into the end of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function rPush($queueName, $value) {
		$this->RequireConnection();
		return $this->client->rpush($queueName, !$value ? $value : $this->serialize($value));
	}

	/**
	 * Puts a value into the beggining of a queue
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function lPush($queueName, $value) {
		$this->RequireConnection();
		return $this->client->lpush($queueName, !$value ? $value : $this->serialize($value));
	}

	/**
	 * Returns the element at the end of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value
	 */
	function rPop($queueName) {
		$this->RequireConnection();
		$r = $this->client->rpop($queueName);
		return !$r ? $r : $this->unserialize($r, true);
	}

	/**
	 * Returns the element at the beggining of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value
	 */
	function lPop($queueName) {
		$this->RequireConnection();
		$r = $this->client->lpop($queueName);
		return !$r ? $r : $this->unserialize($r, true);
	}

	/**
	 * Stores a value in a cache pool
	 *
	 * @param string $poolName The name of the pool
	 * @param string $value The value
	 * @return bool Whether the value has been correctly stored. False otherwise
	 */
	function poolAdd($poolName, $value) {
		$this->RequireConnection();
		return $this->client->sadd($poolName, $value);
	}

	/**
	 * Gets a random value from the pool and removes it
	 *
	 * @param string $poolName The name of the pool
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function poolPop($poolName) {
		$this->RequireConnection();
		return $this->client->spop($poolName);
	}

	/**
	 * Checks whether a value is stored or not in the pool.
	 *
	 * @param string $poolName The name of the pool
	 * @param $value The value
	 * @return bool True if the value exists in the pool, false otherwise
	 */
	function isInPool($poolName, $value) {
		$this->RequireConnection();
		return $this->client->sismember($poolName, $value);
	}

	/**
	 * @param string $poolName The name of the pool
	 * @return integer The number of elements in the pool
	 */
	function poolCount($poolName) {
		$this->RequireConnection();
		return $this->client->scard($poolName);
	}

	/**
	 * Adds an item with the given key with the given value to the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param mixed $value The value
	 * @return integer 1 if the key wasn't on the hash list and it was added. 0 if the key already existed and it was updated.
	 */
	function hSet($listName, $key, $value) {
		$this->RequireConnection();
		return $this->client->hset($listName, $key, $value);
	}

	/**
	 * Retrieves the stored value at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return mixed The stored value
	 */
	function hGet($listName, $key) {
		$this->RequireConnection();
		return $this->client->hget($listName, $key);
	}

	/**
	 * Removes the item at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 */
	function hDel($listName, $key) {
		$this->RequireConnection();
		return $this->client->hdel($listName, $key);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return boolean Whether the item at the given key exists on the specified listName
	 */
	function hExists($listName, $key) {
		$this->RequireConnection();
		return $this->client->hexists($listName, $key);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return integer The number of items stored at the given listName
	 */
	function hLen($listName) {
		$this->RequireConnection();
		return $this->client->hlen($listName);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array of all the items on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function hGetAll($listName) {
		$this->RequireConnection();
		return $this->client->hgetall($listName);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array containing all the keys on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function hgetKeys($listName) {
		$this->RequireConnection();
		return $this->client->hgetkeys($listName);
	}

	/**
	 * Increments the number stored at the given key in the given listName by the given increment
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param integer $increment The amount to increment
	 * @return integer The value after applying the increment
	 */
	function hIncrBy($listName, $key, $increment = 1) {
		$this->RequireConnection();
		return $this->client->hincrby($listName, $key, $increment);
	}
}