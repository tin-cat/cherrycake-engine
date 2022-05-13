<?php

namespace Cherrycake\Modules\Database;

use Cherrycake\Engine;
use Cherrycake\Modules\Cache\Cache;

/**
 * Base class for database provider implementations. Intended to be overloaded by a higher level database system implementation class.
 * Database providers are only connected when required (when the first request is received)
 */
class DatabaseProvider {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"cacheKeyPrefix" => "Database",
		"cacheDefaultTtl" => Cache::TTL_NORMAL,
		"cacheProviderName" => "engine"
	];

	/**
	 * @var array Configuration about fieldtypes (\Cherrycake\Modules\Database\Database::TYPE_*) for each implementation of DatabaseProvider
	 */
	protected $fieldTypes;

	/**
	 * @var bool $isConnected Whether this database is connected to the provider, when needed
	 */
	protected $isConnected = false;

	/**
	 * @var string $resultClassName Holds the name of the class that handles database results. Must be set by an overloaded class
	 */
	protected $resultClassName;

	/**
	 * Creates a database provider-dependant DatabaseResult type object and returns it.
	 * @return DatabaseResult The higher-level DatabaseResult object type
	 */
	function createDatabaseResultObject(): DatabaseResult {
		eval("\$result = new \\Cherrycake\\Modules\\Database\\".$this->resultClassName."();");
		return $result;
	}

	/**
	 * Sets the configuration of the database provider.
	 * @param array $config The database provider parameters
	 */
	function config(array $config) {
		if ($this->config)
			$this->config = array_merge($this->config, $config);
		else
			$this->config = $config;
	}

	/**
	 * Gets a configuration value
	 * @param string $key The configuration key
	 * @return mixed The configuration value
	 */
	function getConfig(string $key): mixed {
		return $this->config[$key];
	}

	/**
	 * Connects to the database provider. Intended to be overloaded by a higher level database system implementation class.
	 * @return bool True if the connection has been established, false otherwise
	 */
	function connect(): bool {}

	/**
	 * Calls the connect method in case this provider is not yet connected
	 * @return bool True if connection is stablished (or has already been stablished), false if connection error
	 */
	function requireConnection(): bool {
		if (!$this->isConnected)
			return $this->connect();
		return true;
	}

	/**
	 * Disconnect from the database provider if needed.
	 * @return bool True if the disconnection has been done, false otherwise
	 */
	function disconnect(): bool {}

	/**
	 * Performs a query to the database Intended to be overloaded by a higher level implementation class.
	 * @param string $sql The SQL query string
	 * @param array $setup Optional array with additional options. See DatabaseResult::$setup for available options
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function query(
		string $sql,
		array $setup = [],
	): DatabaseResult {}

	/**
	 * Performs a query to the database implementing a caching mechanism.
	 * If the query results are stored in the cache, it retrieves them. If not in cache, it performs the query and stores the results in cache.
	 * Stores results in cache in the form of a tridimensional arrays, storing the DatabaseResult->data variable.
	 *
	 * Example:
	 * <code>
	 * $result = Engine::e()->Database->main->QueryCache(
	 * 	"select * from stuff order by rand() limit 3", // The query
	 * 	Cache::TTL_MINIMAL, // The TTL
	 * 	[ // A key naming options array
	 * 		"cacheSpecificPrefix" => "TestQuery"
	 * 	],
	 * 	"engine" // A name of a cache provider that overrides the one configured in database.config.php
	 * );
	 * </code>
	 *
	 * @param string $sql The SQL statement.
	 * @param string $cacheTtl The TTL for the cache results. If not specified, the Database configuration key cacheDefaultTtl is used.
	 * @param array $cacheKeyNamingOptions If specified, takes the configuration keys as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @param string $overrideCacheProviderName The name of the alternative cache provider to use for this query. If specified, it will use this cache provider (as configured in cache.config.php) instead of the one configured in database.config.php
	 * @param boolean $isStoreInCacheWhenNoResults Whether to store results in the cache when the query returned no rows or not.
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResult A provider-specific DatabaseResult class if the query has been executed or retrieved from the cache correctly, false otherwise.
	 */
	function queryCache(
		string $sql,
		bool $cacheTtl = false,
		array $cacheKeyNamingOptions = [],
		bool $overrideCacheProviderName = false,
		bool $isStoreInCacheWhenNoResults = true,
		array $setup = []
	): DatabaseResult {
		if (!$cacheTtl)
			$cacheTtl = $this->getConfig("cacheDefaultTtl");

		$cacheKey = $this->buildQueryCacheKey($sql, $cacheKeyNamingOptions);
		$cacheProviderName = ($overrideCacheProviderName ? $overrideCacheProviderName : $this->getConfig("cacheProviderName"));

		if ($data = Engine::e()->Cache->$cacheProviderName->get($cacheKey)) { // If the data for this query is stored in the cache
			$result = $this->createDatabaseResultObject();
			$result->init(false, $setup);
			$result->setData($data);
			return $result;
		}
		else { // If the data for this query is not stored in the cache
			if ($result = $this->query($sql)) { // Run the query without caching
				if ((!$isStoreInCacheWhenNoResults && $result->isAny()) || $isStoreInCacheWhenNoResults)
					Engine::e()->Cache->$cacheProviderName->set($cacheKey, $result->getData(), $cacheTtl);
			}
			return $result;
		}
	}

	/**
	 * Clears the cache for the query identified by the given cacheKeyNamingOptions
	 * @param array $cacheKeyNamingOptions The cache key naming configuration keys as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @param string $overrideCacheProviderName The name of the alternative cache provider to use for this query. If specified, it will use this cache provider (as configured in cache.config.php) instead of the one configured in database.config.php
	 * @return boolean Whether the cache could be cleared succesfully or not
	 */
	function clearCacheQuery(
		array $cacheKeyNamingOptions,
		bool $overrideCacheProviderName = false,
	): bool {
		$cacheKey = $this->buildQueryCacheKey(false, $cacheKeyNamingOptions);
		$cacheProviderName = ($overrideCacheProviderName ? $overrideCacheProviderName : $this->getConfig("cacheProviderName"));
		return Engine::e()->Cache->$cacheProviderName->delete($cacheKey);
	}

	/**
	 * Builds a cache key that uniquely identifies the query, based on the configuration provided via $cacheKeyNamingConfig
	 * The cache key is always prefixed with the configuration value "cacheKeyPrefix", if set. (For clarity purposes when browsing the cached elements)
	 * It uses MD4 algorithm to create a unique string based on the query because is faster, and we do not require any security here.  MD4 algorithm generates a 32-char hexadecimal code, allowing for 16^32 different keys (approx. 3.4*10^38, 340 undecillion different values)
	 * @param $sql The SQL sentence.
	 * @param array $cacheKeyNamingParameters If specified, takes the parameters as specified in \Cherrycake\Modules\Cache::buildCacheKey as a hash array
	 * @return string The cache key
	 */
	protected function buildQueryCacheKey(
		string $sql,
		?array $cacheKeyNamingParameters = null,
	): string {
		$cacheKeyNamingParameters["prefix"] = $this->getConfig("cacheKeyPrefix");
		$cacheKeyNamingParameters["hash"] = $sql;
		return Cache::buildCacheKey(...$cacheKeyNamingParameters);
	}

	/**
	 * Prepares a query to be done to the dabase using prepared queries methodology. Intended to be overloaded by a higher level implementation class
	 * @param string $sql The SQL sentence to prepare to be queried to the database.
	 * @return array A hash array with the following keys:
	 *  - sql: The passed sql query
	 *  - statement: A provider-specific statement object if the query has been executed correctly, false otherwise.
	 */
	function prepare(string $sql): array {}

	/**
	 * Executes a previously prepared query with the given parameters. Intended to be overloaded by a higher level implementation class
	 *
	 * @param array $prepareResult The prepared result as returned by the prepare method
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Each array element has the following keys:
	 * * type: One of the prepared statement variable type consts, i.e.: \Cherrycake\Modules\Database\Database::TYPE_*
	 * * value: The value to be used for this variable on the prepared statement
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function execute(
		array $prepareResult,
		array $parameters,
		array $setup = [],
	): DatabaseResult {}

	/**
	 * Performs a full prepared query procedure in just one call. Does the same as if we're executing prepare and execute methods separaterly. Intended for performing prepared queries that won't be repeated in a loop (thus we don't need the benefits of separately preparing the query and then executing it multiple times with different values).
	 * @param string $sql The SQL sentence to prepare to be queried to the database.
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Same syntax as in the execute method.
	 * @param array $setup Optional array with additional options, See DatabaseResult::$setup for available options
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function prepareAndExecute(
		string $sql,
		array $parameters = [],
		array $setup = [],
	): DatabaseResult {
		if (!$prepareResult = $this->prepare($sql))
			return false;

		if (!$databaseResult = $this->execute($prepareResult, $parameters, $setup))
			return false;

		return $databaseResult;
	}

	/**
	 * Executes a prepared query with Cache capabilities.
	 * If the prepared query results are stored in the cache, it retrieves it. If not in cache, it normally executes the prepared query and stores the results in cache.
	 * Stores results in cache in the form of a tridimensional arrays, storing the DatabaseResult->data variable.
	 * @param array $prepareResult The prepared result as returned by the prepare method
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Same syntax as in execute method
	 * @param string $cacheTtl The TTL for the cache results. If not specified, configuration value cacheDefaultTtl is used
	 * @param array $cacheKeyNamingOptions If specified, takes the configuration keys as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @param string $overrideCacheProviderName The name of the alternative cache provider to use for this query. If specified, it will use this cache provider (as configured in cache.config.php) instead of the one configured in database.config.php
	 * @param boolean $isStoreInCacheWhenNoResults Whether to store results in the cache when the query returned no rows or not
	 * @param array $setup Optional array with additional options, See DatabaseResult::init for available options
	 * @return DatabaseResult A provider-specific DatabaseResult class if the query has been executed or retrieved from the cache correctly, false otherwise.
	 */
	function executeCache(
		array $prepareResult,
		array $parameters,
		array $cacheTtl = [],
		array $cacheKeyNamingOptions = [],
		string $overrideCacheProviderName = '',
		bool $isStoreInCacheWhenNoResults = true,
		array $setup = [],
	): DatabaseResult {

		if (!$cacheTtl)
			$cacheTtl = $this->getConfig("cacheDefaultTtl");

		$cacheKey = $this->buildPreparedQueryCacheKey($prepareResult["sql"], $parameters, $cacheKeyNamingOptions);
		$cacheProviderName = ($overrideCacheProviderName ? $overrideCacheProviderName : $this->getConfig("cacheProviderName"));

		if ($data = Engine::e()->Cache->$cacheProviderName->get($cacheKey)) { // If the data for this query is stored in the cache
			$result = $this->createDatabaseResultObject();
			$result->init(false, $setup);
			$result->setData($data);
			return $result;
		}
		else { // If the data for this query is not stored in the cache
			if ($result = $this->execute($prepareResult, $parameters, $setup)) { // Run the query without caching
				if ((!$isStoreInCacheWhenNoResults && $result->isAny()) || $isStoreInCacheWhenNoResults)
					Engine::e()->Cache->$cacheProviderName->set($cacheKey, $result->getData(), $cacheTtl);
			}
			return $result;
		}
	}

	/**
	 * Performs a full prepared query procedure in just one call with Cache capabilities. Does the same as if we're executing prepare and execute methods separaterly. Intended for performing prepared queries that won't be repeated in a loop (thus we don't need the benefits of separately preparing the query and then executing it multiple times with different values). Intended to be overloaded.
	 * @param string $sql The SQL sentence to prepare to be queried to the database.
	 * @param array $parameters Hash array of the variables that must be applied to the prepared query in order to execute the final query, in the same order as are stated on the prepared sql. Same syntax as in the execute method.
	 * @param int $cacheTtl The TTL for the cache results. If not specified, configuration value cacheDefaultTtl is used
	 * @param array $cacheKeyNamingOptions If specified, takes the configuration keys as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @param string $overrideCacheProviderName The name of the alternative cache provider to use for this query. If specified, it will use this cache provider (as configured in cache.config.php) instead of the one configured in database.config.php
	 * @param boolean $isStoreInCacheWhenNoResults Whether to store results in the cache when the query returned no rows or not
	 * @param array $setup Optional array with additional options, See DatabaseResult::init for available options
	 * @return DatabaseResult A provider-specific DatabaseResult object if the query has been executed correctly, false otherwise.
	 */
	function prepareAndExecuteCache(
		string $sql,
		array $parameters,
		int $cacheTtl = 0,
		array $cacheKeyNamingOptions = [],
		string $overrideCacheProviderName = '',
		bool $isStoreInCacheWhenNoResults = true,
		array $setup = [],
	): DatabaseResult {
		if (!$prepareResult = $this->prepare($sql))
			return false;

		if (!$databaseResult = $this->executeCache($prepareResult, $parameters, $cacheTtl, $cacheKeyNamingOptions, $overrideCacheProviderName, $isStoreInCacheWhenNoResults, $setup))
			return false;

		return $databaseResult;
	}

	/**
	 * Builds a cache key that uniquely identifies a prepared query with the given parameters, based on the configuration provided via $cacheKeyNamingConfig
	 * @param string $sql The SQL sentence.
	 * @param array $parameters Hash array of the variables to be applied to the prepared query, with the same syntax as in the execute method
	 * @param array $cacheKeyNamingParameters If specified, takes the configuration keys as specified in \Cherrycake\Modules\Cache::buildCacheKey
	 * @return string The cache key
	 */
	protected function buildPreparedQueryCacheKey(
		string $sql,
		array $parameters,
		?array $cacheKeyNamingParameters = null,
	): string {
		$hashString = $sql;

		if ($parameters)
			foreach ($parameters as $parameter)
				$hashString .= $parameter["value"];

		$cacheKeyNamingParameters["hash"] = $hashString;
		return Cache::buildCacheKey(...$cacheKeyNamingParameters);
	}

	/**
	 * Inserts a row into the specified table on the current database, with the given fields.
	 * @param string $table The table name
	 * @param array $fields A hash array of field values
	 * @return bool If everything went ok, the id of the inserted row if the table had an autonumeric field, true if didn't have one. False otherwise.
	 */
	function insert(
		string $tableName,
		array $fields
	): \Cherrycake\Modules\Database\DatabaseResult {
		return $this->prepareAndExecute(
			"insert into ".$tableName." (".implode(", ",array_keys($fields)).") values (".implode(", ", array_fill(0, sizeof($fields), "?"	)).");",
			$fields
		);
	}

	/**
	 * Updates a single row in the database identified by the given $idFieldName and $idFieldValue with the given $fields data. More complex updates should be done by the app by calling other methods on this class like prepareAndExecute
	 * @param string $tableName The table name
	 * @param string $idFieldName The name of the field that uniquely identified the row to be updated
	 * @param mixed $idFieldValue The field value for the row to be update
	 * @param array $fields A hash array of field values
	 * @return boolean True if everything went ok, false otherwise
	 */
	function updateByUniqueField(
		string $tableName,
		string $idFieldName,
		string|int $idFieldValue,
		array $fields,
	): bool {
		$query = "update ".$tableName." set ".implode(" = ?, ",array_keys($fields))." = ? where ".$idFieldName." = ?;";
		$fields[$idFieldName] = [
			"type" => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
			"value" => $idFieldValue
		];
		if ($this->prepareAndExecute(
			$query,
			$fields
		))
			return true;
		else
			return false;
	}

	/**
	 * Deletes a single row in the database identified by the given $idFieldName and $idFieldValue. More complex deletes should be done by the app by calling other methods on this class like prepareAndExecute
	 * @param string $tableName The table name
	 * @param string $idFieldName The name of the field that uniquely identified the row to be updated
	 * @param mixed $idFieldValue The field value for the row to be update
	 * @return boolean True if everything went ok, false otherwise
	 */
	function deleteByUniqueField(
		string $tableName,
		string $idFieldName,
		string|int $idFieldValue,
	): bool {
		$query = "delete from ".$tableName." where ".$idFieldName." = ?;";
		if ($this->prepareAndExecute(
			$query,
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
					"value" => $idFieldValue
				]
			]
		))
			return true;
		else
			return false;
	}

	/**
	 * Treats the given string in order to let it be safely included in an SQL sentence as a string literal. Intended to be overloaded.
	 * @param string $string The safe string
	 * @return string The safe string
	 */
	protected function safeString(string $string): string {}
}