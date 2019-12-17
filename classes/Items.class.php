<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class that provides a way to retrieve, count and treat multiple items based on an App implementation of the get method
 *
 * @package Cherrycake
 * @category Classes
 * @todo  Check the caching and the cache clearing performed by the fillFromParameters, clearCache and buildCacheKeyNamingOptions methods
 */
abstract class Items extends BasicObject implements \Iterator {
	/**
	 * @var string The name of the table where this items reside on the database
	 */
	protected $tableName;

	/**
	 * @var string The name of the Item class to use
	 */
	protected $itemClassName = "Item";

	/**
	 * @var array An array containing the Item objects
	 */
	private $items;

	/**
	 * @var integer Stores the number of resulting items after executing the get method if it's been executed with the numberOf. This stores the entire number of items even when parameters like isPaging have been used when calling the get method.
	 */
	private $totalNumberOf;

	/**
	 * @var string The database provider name to use on the fillFromParameters method
	 */
	protected $databaseProviderName = "main";

	/**
	 * @var boolean Whether to cache the result or not on the fillFromParameters method
	 */
	protected $isCache = false;

	/**
	 * @var integer The cache ttl to use on the fillFromParameters method
	 */
	protected $cacheTtl = \Cherrycake\Modules\CACHE_TTL_NORMAL;

	/**
	 * @var string The name of the cache provider to use on the fillFromParameters method
	 */
	protected $cacheProviderName = false;

	/**
	 * The CachedKeysPool mechanism allows for the wiping of multiple cached queries at once that are related to the same Items set.
	 *
	 * When a cachedKeyPoolsName is specified, all the cache keys for queries performed by this Items object will be remembered in an internal pool. So, when executing the clearCachedKeysPool (executes also on the clearCache method), all the cached queries performed by this Items object will be cleared.
	 * 
	 * For example, when we have an Items object that gets certain items lists by accepts a page parameter for paged results, we don't know in advance how many pages will be cached, nor which pages will be cached, hence preventing us from easily clearing all the cached queries (since each cached items set will have an uncertain cache key that should contain the page number). The CachedKeysPool mechanism adds all the used cache keys to the pool as soon as they're used, so we end having a list of all the used cache keys. The clearCachedKeysPool method loops through that list and removes all the cache entries corresponding to each stored key from cache, effectively clearing all the cached queries related to this Items object.
	 *
	 * It uses the same cacheProviderName as the rest of the Items functionalities.
	 * 
	 * @var string The name of the cachedKeys pool to use. False if no pool of cache keys is to be used.
	 */
	protected $cachedKeysPoolName = false;

	/**
	 * Constructor, allows to create an instance object which automatically fills itself in one of the available forms
	 *
	 * @param array $setup Specifications on how to create the Items object, or an array of objects to fill the list with
	 * @return boolean Whether the object could be initialized ok or not
	 */
	function __construct($setup = false) {
		if (!$setup)
			return true;

		if ($setup["itemClassName"])
			$this->itemClassName = $setup["itemClassName"];

		// Try to guess different type of shortcut calls to the constructor
		if (!$setup["itemClassName"] && !$setup["itemLoadMethod"] && !$setup["p"] && $setup["fillMethod"] != "fromParameters") {
			$setup["fillMethod"] = "fromArray";
			$setup["items"] = $setup;
		}
		
		switch($setup["fillMethod"]) {
			case "fromParameters":
				return $this->fillFromParameters($setup["p"]);
				break;

			case "fromDatabaseResult":
				return $this->fillFromDatabaseResult([
					"itemLoadMethod" => $setup["itemLoadMethod"],
					"databaseResult" => $setup["databaseResult"],
					"keyField" => $setup["keyField"]
				]);
				break;
			case "fromArray":
				return $this->fillFromArray($setup["items"]);
				break;
		}

		return true;
	}

	/**
	 * Determines the Item class name that has to be created. When using a DatabaseRow, the DatabaseRow is passed as an argument to help determine the class name if needed. This is intended to be overloaded when different Item classes must be used depending on the specific implementation. If not overloaded, it just uses $this->itemClassName
	 * @return string The Item class name
	 */
	function getItemClassName($databaseRow = false) {
		return $this->itemClassName;
	}

	/**
	 * Fills the list with Items loaded from the given DatabaseResult object
	 *
	 * Setup keys:
	 *
	 * * databaseResult: The DatabaseResult object
	 * * keyField: The name of the field to be used as the key for the list
	 * * itemLoadMethod: The method to use to load Items, available methods
	 *  - fromDatabaseRow: Uses the method Item::loadFromDatabaseRow to load the item, passing each corresponding DatabaseRow
	 * 	- fromId: Uses the method Item::loadFromId to load the item, passing the value of the field specified by keyField setup variable
	 * * items: An array of objects (should be of the given type itemClassName) to be loaded into the list
	 *
	 * @param array $setup Specifications on how to fill the List with Items with the given DatabaseResult
	 * @return boolean True on success, even if there are no results to fill the list, false on error
	 */
	function fillFromDatabaseResult($setup) {
		if (!$setup["databaseResult"]->isAny())
			return true;

		if ($setup["items"])
			$this->items = $setup["items"];
		else {
			switch ($setup["itemLoadMethod"]) {
				case "fromDatabaseRow":
					while ($databaseRow = $setup["databaseResult"]->getRow()) {
						eval("\$item = ".$this->getItemClassName($databaseRow)."::build([\"loadMethod\" => \"fromDatabaseRow\", \"databaseRow\" => \$databaseRow]);");
						$this->addItem($item, $databaseRow->getField($setup["keyField"]));
					}
					break;

				case "fromId":
					while ($databaseRow = $setup["databaseResult"]->getRow()) {
						eval("\$item = ".$this->getItemClassName($databaseRow)."::build(\"loadMethod\" => \"fromId\", \"id\" => \$databaseRow->getField(\$setup[\"keyField\"])]);");
						$this->addItem($item, $databaseRow->getField($setup["keyField"]));
					}
					break;
			}
		}
	}

	/**
	 * Fills the list with the given arrays
	 * @param array $items An array of items to fill the list with
	 * @return boolean True on success, false on error
	 */
	function fillFromArray($items) {
		while (list($idx, $item) = each($items))
			$this->addItem($item, $idx);
		return true;
	}

	/**
	 * Fills the list with items loaded according to the given parameters. Intended to be overloaded and called from a parent class.
	 * @param array $p A hash array of parameters, with the following possible keys, plush the additional keys specifically needed in each implementation of this class, as specified on the implementation's get overloaded method, if any.
	 *
	 * * keyField: <string> Default: id. The name of the field on the database table that uniquely identifies each item, most probably the primary key.
	 * * selects: <array> Default: All fields from this Object's tableName. An array of select SQL parts to select from. Example: ["tableName.*", "tableName2.id"]
	 * * tables: <array> Default This object's tableName. An array of tables to be used on the SQL query.
	 * * wheres: <array|false> Default: false. An array of wheres, where each item is a hash array containing the following keys:
	 * * * sqlPart: The SQL part of the where, on which each value must represented by a question mark. Example: "fieldName = ?"
	 * * * values: An array specifying each of the values used on the sqlPart, in the same order they're used there. Each item of the array must an array of the following keys:
	 * * * * type: The type of the value, must be one of the \Cherrycake\Modules\DATABASE_FIELD_TYPE_*
	 * * * * value: The value itself
	 * * limit: <integer|false> Default: false. Maximum number of items returned
	 * * order <array|false> Default: false: An ordered array of orders to apply to results, on which each item can be one of the configured in the "orders" parameter
	 * * orders <array|false> The order "random" is implemented by default. A hash array of the available orders to be applied to results, where key is the order name as used in the "order" parameter, and the value is the SQL order part.
	 * * orderRandomSeed <string|false>: The seed to use to randomize results when the "random" order is used.
	 * * isPaging: <true|false> Default: false. Whether to page results based on the given page and itemsPerPage parameters
	 * * page: <integer> Default: 0. The number of page to return when paging is active.
	 * * itemsPerPage: <integer> Default: 10. The number of items per page when paging is active.
	 * * isBuildTotalNumberOfItems: <true|false> Default: false. Whether to return the total number of matching items or not in the "totalNumberOf" results key, not taking into account paging configuration. It takes into account the specified limit, if specified.
	 * * isFillItems: <true|false> Default: true. Whether to return the matching items or not in the "items" results key.
	 * * isForceNoCache: <true|false> Default: false. If set to true, the query won't use cache, even if the object is configured to do so.
	 * * cacheKeyNamingOptions: <array|false> Default: false. If specified, this cacheKeyNamingOptions will be used instead of the ones built byt the buildCacheKeyNamingOptions method. The cache key naming options as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * * isStoreInCacheWhenNoResults: <boolean> Default: true. Whether to store results in cache even when there are no results.
	 *
	 * Stores the results on the following object variables, so they can be later used by other methods:
	 * *	items: An array of objects containing the matched items, if isFillItems has been set to true.
	 * *	totalNumberOf: The total number of matching items found, whether paging has been used or not (it takes into account the specified limit, if specified), if isBuildTotalNumberOfItems has been set to true.
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		global $e;

		self::treatParameters($p, [
			"keyField" => ["default" => "id"],
			"selects" => ["addArrayValuesIfNotExist" => [$this->tableName.".*"]],
			"tables" => ["addArrayValuesIfNotExist" => [$this->tableName]],
			"wheres" => ["default" => false],
			"limit" => ["default" => false],
			"order" => ["default" => false],
			"orders" => ["addArrayKeysIfNotExist" => [
				"random" => "rand(".($p["orderRandomSeed"] ? $p["orderRandomSeed"] : "").")"
			]],
			"orderRandomSeed" => ["default" => false],
			"isPaging" => ["default" => false],
			"page" => ["default" => 0],
			"itemsPerPage" => ["default" => 10],
			"isBuildTotalNumberOfItems" => ["default" => false],
			"isFillItems" => ["default" => true],
			"isForceNoCache" => ["default" => false],
			"cacheKeyNamingOptions" => false,
			"isStoreInCacheWhenNoResults" => ["default" => true]
		]);

		// Build the cacheKeyNamingOptions if needed
		if (!$p["isForceNoCache"] && $this->isCache && !$p["cacheKeyNamingOptions"])
			$p["cacheKeyNamingOptions"] = $this->buildCacheKeyNamingOptions($p);

		// Build $wheres and $fields based on the passed wheres
		if (is_array($p["wheres"]))
			foreach ($p["wheres"] as $where) {
				$wheres[] = $where["sqlPart"];
				if (is_array($where["values"]))
					foreach ($where["values"] as $value)
						$fields[] = $value;
			}

		// Fill this object with the query resulting item objects
		if ($p["isFillItems"]) {
			$sql = "select";
			foreach (array_unique($p["selects"]) as $select)
				$sql .= " ".$select.", ";
			$sql = substr($sql, 0, -2);
			$sql .= " from";
			foreach (array_unique($p["tables"]) as $table)
				$sql .= " ".$table.",";
			$sql = substr($sql, 0, -1);

			if (is_array($wheres)) {
				$sql .= " where ";
				foreach ($wheres as $where)
					$sql .= $where." and ";
				reset ($wheres);
				$sql = substr($sql, 0, -4);
			}

			if (is_array($p["order"])) {
				foreach ($p["order"] as $orderItem) {
					if (array_key_exists($orderItem, $p["orders"])) {
						$orderSql .= $p["orders"][$orderItem].", ";
					}
				}
				if ($orderSql)
					$sql .= " order by ".substr($orderSql, 0, -2);
			}

			if ($p["limit"]) {
				$sql .= " limit ? ";
				$fields[] = [
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $p["limit"]
				];
			}
			else
			if ($p["isPaging"]) {
				$sql .= " limit ?,? ";
				$fields[] = [
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $p["page"] * $p["itemsPerPage"]
				];
				$fields[] = [
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $p["itemsPerPage"]
				];
			}

			if (!$p["isForceNoCache"] && $this->isCache) {
				$result = $e->Database->{$this->databaseProviderName}->prepareAndExecuteCache(
					$sql,
					$fields,
					$this->cacheTtl,
					$p["cacheKeyNamingOptions"],
					$this->cacheProviderName,
					$p["isStoreInCacheWhenNoResults"]
				);

				if ($this->cachedKeysPoolName)
					$this->addCachedKey(\Cherrycake\Modules\Cache::buildCacheKey($p["cacheKeyNamingOptions"]));
			}
			else
				$result = $e->Database->{$this->databaseProviderName}->prepareAndExecute(
					$sql,
					$fields
				);

			if (!$result)
				return false;

			if (!$this->fillFromDatabaseResult([
				"itemLoadMethod" => "fromDatabaseRow",
				"databaseResult" => $result,
				"keyField" => $p["keyField"]
			]))
				return false;
		}

		// Build totalNumberOf
		if ($p["isBuildTotalNumberOfItems"]) {
			$sql = "select count(streams.id) as totalNumberOf from streams";
			if (is_array($wheres))
				$sql .= " where ";
			foreach ($wheres as $where)
				$sql .= $where." and ";
			reset ($wheres);
			$sql = substr($sql, 0, -4);

			if (!$p["isForceNoCache"] && $this->isCache)
				$result = $e->Database->{$this->databaseProviderName}->prepareAndExecuteCache(
					$sql,
					$fields,
					$this->cacheTtl,
					$p["cacheKeyNamingOptions"],
					$this->cacheProviderName,
					$p["isStoreInCacheWhenNoResults"]
				);
			else
				$result = $e->Database->{$this->databaseProviderName}->prepareAndExecute(
					$sql,
					$fields
				);

			if (!$result)
				return false;

			$this->totalNumberOf = $result->getRow()->getField("totalNumberOf");
		}

		return true;
	}

	/**
	 * Builds a suitable cacheKeyNamingOptions array for performing queries and also clearing cache. Takes the same parameters as the fillFromParameters method. Intended to be overloaded.
	 * @param array $p A hash array of options, with the same specs as the one passed to the fillFromParameters method. Only the relevan keys will be used.
	 * @return array A cacheKeyNamingOptions hash array suitable to be used when performing queries to the database or clearing the queries cache.
	 */
	function buildCacheKeyNamingOptions($p = false) {
		return [
			"uniqueId" => md5(serialize($p))
		];
	}

	/**
	 * Clears the cache for the query represented by the given $p parameters, just as they were passed to buildCacheKeyNamingOptions (most probably passed first to fillFromParameters)
	 * @param array $p A hash array of parameters that will be used to build the cache key to clear, so it has to be the same as the parameters passed to buildCacheKeyNamingOptions (and also to fillFromParameters, and to the constructor, if that's the case)
	 * @return boolean True if the cache could be cleared, false otherwise
	 */
	function clearCache($p = false) {
		global $e;
		if (!$this->clearCachedKeysPool())
			return false;
		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : $e->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");
		return $e->Cache->{$cacheProviderName}->delete($e->Cache->buildCacheKey($this->buildCacheKeyNamingOptions($p)));
	}

	/**
	 * Adds the given cache key to the pool of cached keys.
	 * 
	 * @param string $cachedKey The cached key name to add to the CachedKeysPool
	 * @return boolean True if the operation went well, false otherwise.
	 */
	function addCachedKey($cachedKey) {
		global $e;

		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : $e->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");

		return $e->Cache->{$cacheProviderName}->poolAdd(
			$this->cachedKeysPoolName,
			$cachedKey
		);
	}

	/**
	 * When using the CachedKeysPool mechanism, this method removes all the cache entries corresponding to each stored key from cache, effectively clearing all the cached queries related to this Items object.
	 * 
	 * @return boolean True if the cachedKeysPool could be cleared, false otherwise
	 */
	function clearCachedKeysPool() {
		if (!$this->cachedKeysPoolName)
			return true;

		global $e;

		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : $e->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");

		while ($cachedKey = $e->Cache->{$cacheProviderName}->poolPop($this->cachedKeysPoolName)) {
			if (!$e->Cache->{$cacheProviderName}->delete($cachedKey))
				$isErrors = true;
		}
		return !$isErrors;
	}

	/**
	 * Adds an Item to the list with the given key if specified
	 *
	 * @param Item $item The Item to add to the list
	 * @param mixed $key The key used to store the Item on the list. If not specified, the object is stored without a key at the end of the list
	 */
	function addItem($item, $key = false) {
		if ($key)
			$this->items[$key] = $item;
		else
			$this->items[] = $item;
	}

	/**
	 * Checks whether the Item with the given key exists on the list
	 *
	 * @param mixed $key The key of the Item to check
	 * @return bool True if the item exists, false if not
	 */
	function isExists($key) {
		return isset($this->items[$key]);
	}

	/**
	 * @return boolean True when there is at least one Item on the list, false otherwise
	 */
	function isAny() {
		if ($this->totalNumberOf > 0)
			return true;
		return sizeof($this->items) ? true : false;
	}

	/*
	 * @return integer The number of items on the list. False if no items
	 */
	function count() {
		if ($this->totalNumberOf)
			return $this->totalNumberOf;
		if ($count = sizeof($this->items))
			return $count;
		else
			return false;
	}

	/**
	 * Removes the Item with the given key from the list
	 *
	 * @param mixed $key The key to remove
	 * @return bool True if the item has been removed, false if the item doesn't exists
	 */
	function remove($key) {
		if ($this->isExists($key)) {
			unset($this->items[$key]);
			return true;
		}
		else
			return false;
	}

	/**
	 * Finds the item with the given key
	 * 
	 * @param mixed $key The key to find
	 * @return mixed The found Item, or false if it wasn't found
	 */
	function find($key) {
		return $this->isExists($key) ? $this->items[$key] : false;
	}

	/**
	 * @return mixed The Item being currently pointed by the internal pointer, it does not move the pointer. If the internal pointer points beyond the end of the list, or the list is empty, it returns false.
	 */
	function current() {
		return $this->isAny() ? current($this->items) : false;
	}

	/**
	 * @return mixed The key of the list element that is being currently pointed by the internal pointer, it does not move the pointer. If the internal pointer points beyond the end of the list, or the list is empty, it returns null.
	 */
	function key() {
		return $this->isAny() ? key($this->items) : false;
	}

	/**
	 * @return mixed The Item that's next in the list of Items, and advances the interal Items pointer by one. Returns false if there are no more elements
	 */
	function next() {
		return $this->isAny() ? next($this->items) : false;
	}

	/**
	 * @return mixed The previous Item in the list of Items, and rewinds the interal Items pointer by one. Returns false if there are no more elements
	 */
	function prev() {
		return $this->isAny() ? prev($this->items) : false;
	}

	/**
	 * @return mixed Rewinds the internal Items pointer to the first element and returns it. Returns the first element or false if the list is empty.
	 */
	function rewind() {	
		return $this->isAny() ? reset($this->items) : false;
	}

	/**
	 * @return boolean True if the current key exists, false otherwise
	 */
	function valid() {
		return $this->isExists($this->key());
	}

	/**
	 * Filters the items using the passed function.
	 * @param callable $function An anonymous function that will be called for each element on the list, and will receive two parameters: the index of the element and the element itself. This function must return true if the element is to be kept on the list, and false if it's to be removed.
	 */
	function filter($function) {
		if (!$this->isAny())
			return;
		while (list($index, $item) = each($this->items))
			if (!$function($index, $item))
				$this->remove($index);
	}
}