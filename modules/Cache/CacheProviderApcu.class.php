<?php

/**
 * CacheProviderApcu
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * CacheProviderApcu
 *
 * Cache Provider based on APCu. It provides a very fast memory caching but limited to a relatively small amount of cached objects, depending on memory available on the APCu server configuration.
 *
 * @package Cherrycake
 * @category Classes
 */
class CacheProviderApcu extends CacheProvider implements CacheProviderInterface, CacheProviderInterfaceQueue, CacheProviderInterfaceList {
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
	 * @return bool True if the object existed and was deleted. False if id didn't exist, or couldn't be deleted.
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

	/**
	 * Appends a value to the end of a queue.
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function queueRPush($queueName, $value) {
		$queue = $this->getQueueItems($queueName);
		array_push($queue, $value);
		return $this->setQueueItems($queueName, $queue);
	}

	/**
	 * Prepends a value to the beginning of a queue.
	 * @param string $queueName The name of the queue
	 * @param mixed $value The value to store
	 * @return boolean True if everything went ok, false otherwise
	 */
	function queueLPush($queueName, $value) {
		$queue = $this->getQueueItems($queueName);
		array_unshift($queue, $value);
		return $this->setQueueItems($queueName, $queue);
	}

	/**
	 * Returns the item at the end of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value, or null if the queue was empty
	 */
	function queueRPop($queueName) {
		$queue = $this->getQueueItems($queueName);
		$item = array_pop($queue);
		$this->setQueueItems($queueName, $queue);
		return $item;
	}

	/**
	 * Returns the element at the beggining of a queue, and removes it
	 * @param string $queueName The name of the queue
	 * @return mixed The stored value, or null if the queue was empty
	 */
	function queueLPop($queueName) {
		$queue = $this->getQueueItems($queueName);
		$item = array_shift($queue);
		$this->setQueueItems($queueName, $queue);
		return $item;
	}

	/**
	 * @param string $queueName The name of the queue
	 * @return string The key that identifies the given queue's items in the cache
	 */
	function getQueueKey($queueName) {
		return "Queue_".$queueName;
	}

	/**
	 * @param string $queueName The name of the queue
	 * @return array The items in the given queue
	 */
	function getQueueItems($queueName) {
		if ($value = $this->get($this->getQueueKey($queueName)))
			return $this->unserialize($value);
		else
			return [];
	}

	/**
	 * Sets the given queue to contain the given items
	 * @param string $queueName The name of the queue
	 * @param array The items for the given queue
	 */
	function setQueueItems($queueName, $items) {
		return $this->set($this->getQueueKey($queueName), $this->serialize($items), 0);
	}

	/**
	 * Adds an object to a list
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param mixed $value The value
	 * @return integer True if the key wasn't on the hash list and it was added. False if the key already existed and it was updated.
	 */
	function listSet($listName, $key, $value) {
		$list = $this->getListItems($listName);
		$isExists = in_array($key, $list);
		$list[$key] = $value;
		$this->setListItems($listName, $list);
		return $isExists;
	}

	/**
	 * Retrieves an object from a list
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return mixed The stored value, or null if it doesn't exists.
	 */
	function listGet($listName, $key) {
		$list = $this->getListItems($listName);		
		return $list[$key] ?? null;
	}

	/**
	 * Removes the item at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 */
	function listDel($listName, $key) {
		$list = $this->getListItems($listName);
		if (isset($list[$key]))
			unset($list[$key]);
		$this->setListItems($listName, $list);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return boolean Whether the item at the given key exists on the specified listName
	 */
	function listExists($listName, $key) {
		$list = $this->getListItems($listName);
		return isset($list[$key]);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return integer The number of items stored at the given listName
	 */
	function listLen($listName) {
		return sizeof($this->getListItems($listName));
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array of all the items on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function listGetAll($listName) {
		return $this->getListItems($listName);
	}

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array containing all the keys on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function listGetKeys($listName) {
		return array_keys($this->listGetAll($listName));
	}

	/**
	 * Increments the number stored at the given key in the given listName by the given increment
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param integer $increment The amount to increment
	 * @return integer The value after applying the increment
	 */
	function listIncrBy($listName, $key, $increment = 1) {
		$list = $this->getListItems($listName);
		$isExists = in_array($key, $list);
		$list[$key] += $increment;
		$this->setListItems($listName, $list);
		return $list[$key];
	}

	/**
	 * @param string $listName The name of the list
	 * @return string The key that identified the given list's items in the cache
	 */
	function getListKey($listName) {
		return "List_".$listName;
	}

	/**
	 * @param string $listName The name of the list
	 * @return array The items in the given list
	 */
	function getListItems($listName) {
		if ($value = $this->get($this->getListKey($listName)))
			return $this->unserialize($value);
		else
			return [];
	}

	/**
	 * Sets the given list to contain the given items
	 * @param string $listName The name of the list
	 * @param array The items dor the given list
	 */
	function setListItems($listName, $items) {
		return $this->set($this->getListKey($listName), $this->serialize($items), 0);
	}
}