<?php

/**
 * CacheProviderMemcached
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * CacheProviderMemcached
 *
 * Cache Provider based on Memcached. It provides a relatively fast memory caching (slower than APC, though), but normally allows huge amounts of objects and big objects to be stored. Memcached daemon can also be setup to run in a server cluster.
 *
 * @package Cherrycake
 * @category Classes
 */
class CacheProviderMemcached extends CacheProvider implements CacheProviderInterface {
	/**
	 * Configuration keys:
	 *
	 * * isPersistentConnection: Whether to use a persistent connection to Memcached servers or not. This is actually only taken into account on the Disconnect method due to the way Memcached works.
	 * * isCompression: Whether to compress cache or not. Defaulted to false. When isCompression is set to false, the method append is much faster.
	 *
	 * @var array $config Holds the configuration of the cache provider when needed; things like host, port and password. This is set by the Cache object from the Cherrycake configuration files when this cache provider is setup.
	 */
	protected $config = [
		"isPersistentConnection" => true,
		"isCompression" => false
	];

	/**
	 * @var Memcached Holds the Memcached object
	 */
	var $memcached;

	/**
	 * connect
	 *
	 * Connects to the cache provider if needed. Intended to be overloaded by a higher level cache system implementation class.
	 */
	function connect() {
		$this->memcached = new \Memcached;
		$this->memcached->setOption(\Memcached::OPT_COMPRESSION, $this->getConfig("isCompression"));
		if (!$this->memcached->addServers($this->GetConfig("servers")))
		{
			global $e;
			$e->Errors->Trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error connecting to Memcached"]);
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
		if (!$this->GetConfig("isPersistentConnection"))
			if (!$this->memcached->quit())
			{
				global $e;
				$e->Errors->Trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Error disconnecting from Memcached"]);
				return false;
			}

		return true;
	}

	/**
	 * set
	 *
	 * Stores a value in the Memcached cache.
	 *
	 * @param string $key The identifier key
	 * @param mixed $value The value
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function set($key, $value, $ttl = false) {
		$this->RequireConnection();
		return $this->memcached->set($key, $value, (!$ttl ? 0 : time() + $ttl));
	}

	/**
	 * get
	 *
	 * Gets a value from the Memcached cache.
	 *
	 * @param string $key The identifier key
	 * @return mixed The stored value or false if it doesn't exists.
	 */
	function get($key) {
		$this->RequireConnection();
		return $this->memcached->get($key);
	}

	/**
	 * delete
	 *
	 * Deletes a value from the Memcached cache.
	 *
	 * @param string $key The identifier key for the object to be deleted
	 * @return bool True if the object existed and was deleted. False if id didn't exist, or couldn't be deleted.
	 */
	function delete($key) {
		$this->RequireConnection();
		return $this->memcached->delete($key, 0);
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
		return $this->memcached->increment($key, $step);
	}

	/**
	 * append
	 *
	 * Appends the given value to the existent value in the cache. The key must already exists, or error will be returned.
	 *
	 * The $ttl parameter is ignored for Memcached since memcached does not provides a way of changing the TTL when appending contents to an existent cache item.
	 *
	 * When the config key "isCompression" is set to false, this method is much faster because it can use Memcached's own "append" mechanism.
	 *
	 * @param string $key The item key to append the given value to
	 * @param string $value The string to append
	 * @param integer $ttl The TTL (Time To Live) of the stored value in seconds since now
	 * @return bool Wether the value has been correctly stored. False otherwise
	 */
	function append($key, $value, $ttl = false) {
		$this->RequireConnection();
		if ($this->getConfig("isCompression"))
			return $this->set($key, $this->get($key).$value, $ttl);
		else
			return $this->memcached->append($key, $value);
	}

	/**
	 * isKey
	 *
	 * Checks whether a value is stored or not in the Memcached cache. Since Memcached hasn't an specific method to check for the existence of an object, a workaround is done here (probably slow)
	 *
	 * @param $key The identifier key
	 * @return bool True if the value exists in the cache, false otherwise
	 */
	function isKey($key) {
		$this->RequireConnection();
		$this->Get($key);
		if ($this->memcached->getResultCode() == \Memcached::RES_NOTFOUND)
			return false;
		else
			return true;
	}

	/**
	 * touch
	 *
	 * Stablishes a new expiration TTL for an element in the Memcached cache.
	 *
	 * @param string $key The identifier key for the object to be touched
	 * @param integer $ttl The new TTL (Time To Live) for the stored value in seconds
	 */
	function touch($key, $ttl) {
		$this->RequireConnection();
		$this->memcached->touch($key, $ttl);
	}
}