<?php

namespace Cherrycake\Classes;

use Cherrycake\BasicObject;
use Cherrycake\Modules\Cache\Cache;
use Cherrycake\Modules\Database\DatabaseRow;
use Cherrycake\Modules\Database\DatabaseResult;

/**
 * Class that provides a way to retrieve, count and treat multiple items based on an App implementation of the get method
 * @todo  Check the caching and the cache clearing performed by the fillFromParameters, clearCache and buildCacheKeyNamingOptions methods
 */
abstract class Items implements \Iterator {

	const FILL_METHOD_FROM_ARRAY = 0;
	const FILL_METHOD_FROM_PARAMETERS = 1;
	const FILL_METHOD_FROM_DATABASERESULT = 2;

	const ITEM_LOAD_METHOD_FROM_DATABASEROW = 0;
	const ITEM_LOAD_METHOD_FROM_ID = 1;

	/**
	 * @var string The class name of the items that will be loaded in this object
	 */
	protected string $itemClassName;

	/**
	 * @var array An array containing the Item objects
	 */
	private array $items = [];

	/**
	 * @var int Stores the number of resulting items after executing the get method if it's been executed with the numberOf. This stores the entire number of items even when parameters like isPaging have been used when calling the get method.
	 */
	private int $totalNumberOf = 0;

	/**
	 * @var string The database provider name to use on the fillFromParameters method
	 */
	protected string $databaseProviderName = "main";

	/**
	 * @var boolean Whether to cache the result or not on the fillFromParameters method
	 */
	protected bool $isCache = false;

	/**
	 * @var int The cache ttl to use on the fillFromParameters method
	 */
	protected int $cacheTtl = Cache::TTL_NORMAL;

	/**
	 * @var string The name of the cache provider to use on the fillFromParameters method
	 */
	protected string $cacheProviderName = "engine";

	/**
	 * The CachedKeysPool mechanism allows for the wiping of multiple cached queries at once that are related to the same Items set.
	 * When a cachedKeyPoolsName is specified, all the cache keys for queries performed by this Items object will be remembered in an internal pool. So, when executing the clearCachedKeysPool (executes also on the clearCache method), all the cached queries performed by this Items object will be cleared.
	 * For example, when we have an Items object that gets certain items lists by accepting a page parameter for paged results, we don't know in advance how many pages will be cached, nor which pages will be cached, hence preventing us from easily clearing all the cached queries (since each cached items set will have an uncertain cache key that should contain the page number). The CachedKeysPool mechanism adds all the used cache keys to the pool as soon as they're used, so we end having a list of all the used cache keys. The clearCachedKeysPool method loops through that list and removes all the cache entries corresponding to each stored key from cache, effectively clearing all the cached queries related to this Items object.
	 * It uses the same cacheProviderName as the rest of the Items functionalities.
	 *
	 * @var string The name of the cachedKeys pool to use. False if no pool of cache keys is to be used.
	 */
	protected string $cachedKeysPoolName;

	/**
	 * Constructor, allows to create an instance object which automatically fills itself in one of the available forms
	 * @param array $parameters The parameters use to fill the items when using the fillFromParameters fillMethod
	 * @param array $items The Items to use when using the fromArray fillMethod
	 * @param int $fillMethod The fill method to use to fill this object with items, one of the self::FILL_METHOD_FROM_*
	 * @param int $itemLoadMethod The load method to use to load items, one of the self::ITEM_LOAD_METHOD_FROM_*
	 * @param DatabaseResult $databaseResult The result to use when using FILL_METHOD_FROM_DATABASERESULT
	 * @param string $keyField The name of the field to use as key for the list
	 */
	function __construct(
		?array $parameters = null,
		?array $items = null,
		?int $fillMethod = null,
		?int $itemLoadMethod = null,
		?DatabaseResult $databaseResult = null,
		?string $keyField = null,
	) {

		// If fillMethod not specified, guess it by the passed parameters
		if (is_null($fillMethod)) {
			if ($items)
				$fillMethod = self::FILL_METHOD_FROM_ARRAY;
			else
			if ($parameters)
				$fillMethod = self::FILL_METHOD_FROM_PARAMETERS;
			else
			if ($databaseResult)
				$fillMethod = self::FILL_METHOD_FROM_DATABASERESULT;
			else
				return;
		}

		switch ($fillMethod) {
			case self::FILL_METHOD_FROM_PARAMETERS:
				return $this->fillFromParameters(...$parameters);
				break;

			case self::FILL_METHOD_FROM_DATABASERESULT:
				return $this->fillFromDatabaseResult(
					itemLoadMethod: $itemLoadMethod,
					databaseResult: $databaseResult,
					keyField: $keyField,
				);
				break;
			case self::FILL_METHOD_FROM_ARRAY:
				return $this->fillFromArray($items);
				break;
		}
	}

	/**
	 * Determines the Item class name that has to be created. When using a DatabaseRow, the DatabaseRow is passed as an argument to help determine the class name if needed. This is intended to be overloaded when different Item classes must be used depending on the specific implementation. If not overloaded, it just uses $this->itemClassName
	 * @return string The Item class name
	 */
	function getItemClassName(?DatabaseRow $databaseRow): string {
		return $this->itemClassName;
	}

	/**
	 * Fills the list with Items loaded from the given DatabaseResult object
	 * @param DatabaseResult $databaseResult The result to use when using ITEM_LOAD_METHOD_FROM_DATABASEROW
	 * @param array $items The items to fill this object with
	 * @param int $itemLoadMethod The method to use to load items, one of the ITEM_LOAD_METHOD_FROM_*
	 * @param string $keyField the name of the field to use as key for the list
	 * @return boolean True on success, even if there are no results to fill the list, false on error
	 */
	function fillFromDatabaseResult(
		DatabaseResult $databaseResult,
		?array $items = null,
		int $itemLoadMethod = self::ITEM_LOAD_METHOD_FROM_DATABASEROW,
		?string $keyField = null,
	): bool {
		if (!$databaseResult->isAny())
			return true;

		if ($items) {
			$this->items = $items;
			return true;
		}

		switch ($itemLoadMethod) {
			case self::ITEM_LOAD_METHOD_FROM_DATABASEROW:
				while ($databaseRow = $databaseResult->getRow()) {
					eval("\$item = new ".$this->getItemClassName($databaseRow)."(loadMethod: \"fromDatabaseRow\", databaseRow: \$databaseRow);");
					$this->addItem($item, $databaseRow->getField($keyField));
				}
				break;

			case self::ITEM_LOAD_METHOD_FROM_ID:
				while ($databaseRow = $databaseResult->getRow()) {
					eval("\$item = new ".$this->getItemClassName($databaseRow)."(loadMethod: \"fromId\", id: \$databaseRow->getField(\$setup[\"keyField\"]));");
					$this->addItem($item, $databaseRow->getField($keyField));
				}
				break;
		}

		return true;
	}

	/**
	 * Fills the list with the given arrays
	 * @param array $items An array of items to fill the list with
	 * @return boolean True on success, false on error
	 */
	function fillFromArray(array $items): bool {
		foreach ($items as $idx => $item)
			$this->addItem($item, $idx);
		return true;
	}

	/**
	 * Fills the list with items loaded according to the given parameters. Intended to be overloaded and called from a parent class.
	 * Stores the results on the following object variables, so they can be later used by other methods:
	 * - items: An array of objects containing the matched items, if isFillItems has been set to true.
	 * - totalNumberOf: The total number of matching items found, whether paging has been used or not (it takes into account the specified limit, if specified), if isBuildTotalNumberOfItems has been set to true.
	 *
	 * @param string $keyField The name of the field on the database table that uniquely identifies each item, most probably the primary key.
	 * @param array $selects An array of select SQL parts to select from. Example: ["tableName.*", "tableName2.id"]. All fields are selectede if not specified.
	 * @param array $tables An array of tables to be used on the SQL query. If not specified the tableName of this object's $itemClassName is used
	 * @param array $wheres An array of where SQL clauses, where each item is a hash array containing the following keys:
	 * - sqlPart: The SQL part of the where, on which each value must represented by a question mark. Example: "fieldName = ?"
	 * - values: An array specifying each of the values used on the sqlPart, in the same order they're used there. Each item of the array must an array of the following keys:
	 * -- type: The type of the value, must be one of the \Cherrycake\Modules\Database\Database::TYPE_*
	 * -- value: The value
	 * @param int $limit Maximum number of items returned. All items are selected if not specified
	 * @param array $order An ordered array of orders to apply to results, on which each item can be one of the configured in the $orders parameter
	 * @param array $orders A hash array of the available orders to be applied to results, where each key is the order name as used in the "order" parameter, and the value is the SQL order part. The order 'random' is implemented by default.
	 * @param string $orderRandomSeed The seed to use to randomize results when the 'random' order is used
	 * @param bool $isPaging Whether to page results based on the given page and itemsPerPage parameters
	 * @param int $page The number of page to return when paging is active
	 * @param int $itemsPerPage The number of items per page when paging is active
	 * @param bool $isBuildTotalNumberOfItems Whether to return the total number of matching items or not in the "totalNumberOf" results key, not taking into account paging configuration. It takes into account limit, if specified.
	 * @param bool $isFillItems Whether to return the matching items or not in the "items" results key.
	 * @param bool $isForceNoCache If set to true, the query won't use cache, even if the object is configured to do so.
	 * @param array $cacheKeyNamingParameters If specified, this array of parameterName => value will be used instead of the ones built by the buildCacheKeyNamingOptions method. The cache key naming options as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @param bool $isStoreInCacheWhenNoResults Whether to store results in cache even when there are no results
	 * @return boolean True if everything went ok, false otherwise
	 */
	function fillFromParameters(
		string $keyField = 'id',
		?array $selects = null,
		?array $tables = null,
		?array $wheres = null,
		?int $limit = null,
		?array $order = null,
		?array $orders = null,
		?string $orderRandomSeed = null,
		bool $isPaging = false,
		int $page = 0,
		int $itemsPerPage = 10,
		bool $isBuildTotalNumberOfItems = false,
		bool $isFillItems = true,
		bool $isForceNoCache = false,
		?array $cacheKeyNamingParameters = null,
		bool $isStoreInCacheWhenNoResults = true
	): bool {
		if (!$selects)
			$selects = [$this->itemClassName::$tableName.'.*'];

		if (!$tables)
			$tables = [$this->itemClassName::$tableName];

		if (!isset($orders['random']))
			$orders['random'] = 'rand('.($p['orderRandomSeed'] ?? '').')';

		// Build the cacheKeyNamingOptions if needed
		if (!$isForceNoCache && $this->isCache && !$cacheKeyNamingParameters)
			$cacheKeyNamingParameters = $this->buildCacheKeyNamingOptions(func_get_args());

		// Build $whereSqlParts and $fields based on the passed wheres
		if ($wheres) {
			foreach ($wheres as $where) {
				$whereSqlParts[] = $where['sqlPart'];
				if (isset($where['values']))
					foreach ($where['values'] as $value)
						$fields[] = $value;
			}
		}

		// Fill this object with the query resulting item objects
		if ($isFillItems) {
			$sql =
				'select '.
				implode(', ', array_unique($selects)).
				' from '.
				implode(', ', array_unique($tables)).
				(isset($whereSqlParts) ?
					' where '.
					implode(' and ', $whereSqlParts)
				: null);

			if (is_array($order)) {
				$orderSql = false;
				foreach ($order as $orderItem) {
					if (array_key_exists($orderItem, $orders ?? [])) {
						$orderSql .= $orders[$orderItem].', ';
					}
				}
				if ($orderSql)
					$sql .= ' order by '.substr($orderSql, 0, -2);
			}

			if ($limit) {
				$sql .= ' limit ? ';
				$fields[] = [
					'type' => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
					'value' => $limit
				];
			}
			else
			if ($isPaging) {
				$sql .= ' limit ?,? ';
				$fields[] = [
					'type' => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
					'value' => $page * $itemsPerPage
				];
				$fields[] = [
					'type' => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
					'value' => $itemsPerPage
				];
			}

			if (!$isForceNoCache && $this->isCache) {
				$result = Engine::e()->Database->{$this->databaseProviderName}->prepareAndExecuteCache(
					$sql,
					$fields,
					$this->cacheTtl,
					$cacheKeyNamingOptions,
					$this->cacheProviderName,
					$isStoreInCacheWhenNoResults
				);

				if ($this->cachedKeysPoolName)
					$this->addCachedKey(Cache::buildCacheKey(...$cacheKeyNamingOptions));
			}
			else
				$result = Engine::e()->Database->{$this->databaseProviderName}->prepareAndExecute(
					$sql,
					$fields ?? []
				);

			if (!$result)
				return false;

			if (!$this->fillFromDatabaseResult(
				itemLoadMethod: self::ITEM_LOAD_METHOD_FROM_DATABASEROW,
				databaseResult: $result,
				keyField: $keyField
			))
				return false;
		}

		// Build totalNumberOf
		if ($isBuildTotalNumberOfItems) {
			$sql = 'select count('.$this->itemClassName::$tableName.'.id) as totalNumberOf from '.$this->itemClassName::$tableName;
			if (is_array($wheres))
				$sql .= ' where ';
			foreach ($wheres as $where)
				$sql .= $where.' and ';
			reset ($wheres);
			$sql = substr($sql, 0, -4);

			if (!$isForceNoCache && $this->isCache)
				$result = Engine::e()->Database->{$this->databaseProviderName}->prepareAndExecuteCache(
					$sql,
					$fields,
					$this->cacheTtl,
					$cacheKeyNamingOptions,
					$this->cacheProviderName,
					$isStoreInCacheWhenNoResults
				);
			else
				$result = Engine::e()->Database->{$this->databaseProviderName}->prepareAndExecute(
					$sql,
					$fields
				);

			if (!$result)
				return false;

			$this->totalNumberOf = $result->getRow()->getField('totalNumberOf');
		}

		return true;
	}

	/**
	 * Builds a suitable cacheKeyNamingOptions array for performing queries and also clearing cache. Intended to be overloaded.
	 * Takes the same parameters as the fillFromParameters method.
	 * @return array A cacheKeyNamingOptions hash array suitable to be used when performing queries to the database or clearing the queries cache.
	 */
	function buildCacheKeyNamingOptions(...$parameters): array {
		return [
			'uniqueId' => md5(serialize($parameters))
		];
	}

	/**
	 * Clears the cache for the query represented by the given p parameters, just as they were passed to buildCacheKeyNamingOptions, the constructor or, most probably, the fillFromParameters method.
	 * @param array $p A hash array of parameters that will be used to build the cache key to clear, so it has to be the same as the parameters passed to buildCacheKeyNamingOptions (and also to fillFromParameters, and to the constructor, if that's the case)
	 * @return boolean True if the cache could be cleared, false otherwise
	 */
	function clearCache(...$parameters) {
		if (!$this->clearCachedKeysPool())
			return false;
		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : Engine::e()->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");
		return Engine::e()->Cache->{$cacheProviderName}->delete(Engine::e()->Cache->buildCacheKey(...$this->buildCacheKeyNamingOptions($parameters)));
	}

	/**
	 * Adds the given cache key to the pool of cached keys.
	 *
	 * @param string $cachedKey The cached key name to add to the CachedKeysPool
	 * @return boolean True if the operation went well, false otherwise.
	 */
	function addCachedKey($cachedKey) {

		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : Engine::e()->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");

		return Engine::e()->Cache->{$cacheProviderName}->poolAdd(
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


		// If a cacheProviderName is provided for this object, use it to clear cache also, which it's also been used on fillFromParameters. If not, get the databaseProvider default cacheProviderName, which is also the one that's being used on fillFromParameters
		$cacheProviderName = $this->cacheProviderName ? $this->cacheProviderName : Engine::e()->Database->{$this->databaseProviderName}->getConfig("cacheProviderName");

		while ($cachedKey = Engine::e()->Cache->{$cacheProviderName}->poolPop($this->cachedKeysPoolName)) {
			if (!Engine::e()->Cache->{$cacheProviderName}->delete($cachedKey))
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
		if (is_array($this->items))
			return sizeof($this->items) > 0;
		else
			return false;
	}

	/*
	 * @return integer The number of items on the list.
	 */
	function count() {
		if ($this->totalNumberOf)
			return $this->totalNumberOf;
		if (!is_array($this->items))
			return 0;
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
		foreach ($this->items as $index => $item)
			if (!$function($index, $item))
				$this->remove($index);
	}

	/**
	 * @return array An array of Item objects
	 */
	function toArray(): array {
		return $this->items;
	}
}
