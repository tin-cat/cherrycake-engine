<?php

namespace Cherrycake\Cache;

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
	protected array $config = [];

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
	function config(array $config) {
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
