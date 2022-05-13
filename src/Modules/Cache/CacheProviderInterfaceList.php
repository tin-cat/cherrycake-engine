<?php

namespace Cherrycake\Modules\Cache;

/**
 * Additional interface for all cache providers that additionally implement Hashed lists functionalities
 * Hashed lists allow you to store sets of items identified by a specific key, into a hash list identified also by its own key. You can then get or remove specific items from the list, or remove all items of a list at the same time.
 */
interface CacheProviderInterfaceList {
	/**
	 * Adds an item with the given key with the given value to the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param mixed $value The value
	 * @return integer 1 if the key wasn't on the hash list and it was added. 0 if the key already existed and it was updated.
	 */
	function listSet($listName, $key, $value);

	/**
	 * Retrieves the stored value at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return mixed The stored value
	 */
	function listGet($listName, $key);

	/**
	 * Removes the item at the given key from the given listName
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 */
	function listDel($listName, $key);

	/**
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @return boolean Whether the item at the given key exists on the specified listName
	 */
	function listExists($listName, $key);

	/**
	 * @param string $listName The name of the hashed list
	 * @return integer The number of items stored at the given listName
	 */
	function listLen($listName);

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array of all the items on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function listGetAll($listName);

	/**
	 * @param string $listName The name of the hashed list
	 * @return array An array containing all the keys on the specified list. An empty array if the list was empty, or false if the list didn't exists.
	 */
	function listGetKeys($listName);

	/**
	 * Increments the number stored at the given key in the given listName by the given increment
	 * @param string $listName The name of the hashed list
	 * @param string $key The key
	 * @param integer $increment The amount to increment
	 * @return integer The value after applying the increment
	 */
	function listIncrBy($listName, $key, $increment = 1);
}
