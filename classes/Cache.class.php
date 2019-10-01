<?php

/**
 * Cache
 *
 * @package Movefy
 */

namespace Cherrycake;

/**
 * Provides a bottom level cache aimed to provide engine-level speed optimizations with a small-size, high performance cache.
 * This cache mechanism is not intended to be used in the Application layers, use Cache module instead.
 *
 * @package Cherrycake
 * @category Classes
 */
class Cache {
	private $defaultBucketName = "default";

	/**
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return string The cache key
	 */
	function buildKey($id) {
		return
			"Cherrycake_".
			APP_NAME."_".
			(is_array($id) ? implode("_", $id) : $id);
	}

	/**
	 * @param string $bucket The bucket name
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return string The cache key
	 */
	function buildKeyForBucket($bucket, $id) {
		return
			"Cherrycake_".
			APP_NAME."_".
			$bucket."_".
			(is_array($id) ? implode("_", $id) : $id);
	}

	/**
	 * Sets a value in the low level cache mechanism, intended to be used only internally within this class
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @param mixed $value The value to store
	 * @param int $ttl The time to live for the store item in seconds
	 * @return boolean True if success, false otherwise
	 */
	private function setKey($key, $value, $ttl = 0) {
		return apcu_store($key, $value, $ttl);
	}

	/**
	 * Gets a value from the low level cache mechanism, intended to be used only internally within this class
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return mixed The stored value, or false if there was no stored value
	 */
	private function getKey($key) {
		return apcu_fetch($key);
	}

	/**
	 * Removes an item from the low level cache mechanism, intended to be used only internally within this class
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return boolean True if success, false otherwise
	 */
	private function clearKey($key) {
		return apcu_delete($key);
	}

	/**
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @param mixed $value The value to store
	 * @param int $ttl The time to live for the store item in seconds
	 * @return boolean True if success, false otherwise
	 */
	function set($id, $value, $ttl = 0) {
		return $this->setInBucket($this->defaultBucketName, $id, $value, $ttl);
	}

	/**
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return mixed The stored value, or false if there was no stored value
	 */
	function get($id) {
		return $this->getFromBucket($this->defaultBucketName, $id);
	}

	/**
	 * Removes an item from the cache
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return boolean True if success, false otherwise
	 */
	function clear($id) {
		return $this->clearFromBucket($this->defaulBucketName, $id);
	}

	/**
	 * @param string $bucket The bucket name
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @param mixed $value The value to store
	 * @param int $ttl The time to live for the store item in seconds
	 * @return boolean True if success, false otherwise
	 */
	function setInBucket($bucket, $id, $value, $ttl = 0) {
		$key = $this->buildKeyForBucket($bucket, $id);
		$this->addKeyToBucket($bucket, $key);
		return $this->setKey($key, $value, $ttl);
	}

	/**
	 * @param string $bucket The bucket name
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return mixed The stored value, or false if there was no stored value
	 */
	function getFromBucket($bucket, $id) {
		return $this->getKey($this->buildKeyForBucket($bucket, $id));
	}

	/**
	 * Removes an item from a bucket from the cache
	 * @param string $bucket The bucket name
	 * @param mixed $id A string, or an array of strings that uniquely identify an item in the cache
	 * @return boolean True if success, false otherwise
	 */
	function clearFromBucket($bucket, $id) {
		$key = $this->buildKeyForBucket($bucket, $id);
		$this->removeKeyFromBucket($bucket, $key);
		return $this->clearKey($key);
	}

	/**
	 * Removes all the items in a bucket
	 * @param string $bucket The bucket name
	 * @return boolean True if success, false otherwise
	 */
	function clearBucket($bucket) {
		if (!$keys = $this->getKeysInBucket($bucket))
			return true;
		
		foreach ($keys as $key) {
			if (!$this->clearKey($key))
				$isError = true;
			if (!$this->removeKeyFromBucket($bucket, $key))
				$isError = true;
		}

		return $isError;
	}

	/**
	 * @return boolean Whether the specified item from the specified bucket in the cache exists
	 */
	function isKeyExistsInBucket($bucket, $id) {
		return apcu_exists($this->buildKeyForBucket($bucket, $id));
	}

	/**
	 * @param string $bucket The bucket name
	 * @return array The keys stored in the specified bucket
	 */
	function getKeysInBucket($bucket) {
		return $this->getKey($this->buildKey(["bucketKeys", $bucket]));
	}

	/**
	 * Adds a key to the list of keys contained in a bucket
	 * @param string $bucket The bucket name
	 * @param string $key The key
	 */
	function addKeyToBucket($bucket, $key) {
		$keysInBucket = $this->getKeysInBucket($bucket);
		if (is_array($keysInBucket) && in_array($key, $keysInBucket))
			return;
		$keysInBucket[] = $key;
		$this->setKey($this->buildKey(["bucketKeys", $bucket]), $keysInBucket);

		$buckets = $this->get("buckets");
		if (is_array($buckets) && !in_array($bucket, $buckets))
			$buckets[] = $bucket;
		else
			$buckets[] = $bucket;
		$this->setKey($this->buildKey(["buckets"]), $buckets);
	}

	/**
	 * Removes a key from the list of keys contained in a bucket
	 * @param string $bucket The bucket name
	 * @param string $key The key
	 */
	function removeKeyFromBucket($bucket, $key) {
		$keysInBucket = $this->getKeysInBucket($bucket);
		$keysInBucket = array_diff($keysInBucket, [$key]);
		if (is_array($keysInBucket)) {
			$this->setKey($this->buildKey(["bucketKeys", $bucket]), $keysInBucket);
		}
	}

	/**
	 * @return array The names of all the buckets
	 */
	function getBuckets() {
		return $this->getKey($this->buildKey(["buckets"]));
	}

	/**
	 * Clears the entire cache
	 */
	function reset() {
		apcu_clear_cache();
	}

	/**
	 * @return array A hash array containing information about the current status of the cache
	 */
	function getStatus() {
		$smaInfo = apcu_sma_info();
		$stats["availableMemory"] = $smaInfo["avail_mem"];

		$info = apcu_cache_info();
		$stats["hits"] = $info["num_hits"];
		$stats["misses"] = $info["num_misses"];

		if ($buckets = $this->getBuckets()) {
			foreach ($buckets as $bucket) {
				$stats["buckets"][$bucket] = $this->getKeysInBucket($bucket);
			}
		}
		return $stats;
	}
}