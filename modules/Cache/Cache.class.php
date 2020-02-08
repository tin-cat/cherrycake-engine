<?php

/**
 * Cache
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const CACHE_TTL_1_MINUTE = 60;
const CACHE_TTL_5_MINUTES = 300;
const CACHE_TTL_10_MINUTES = 600;
const CACHE_TTL_30_MINUTES = 1800;
const CACHE_TTL_1_HOUR = 3600;
const CACHE_TTL_2_HOURS = 7200;
const CACHE_TTL_6_HOURS = 21600;
const CACHE_TTL_12_HOURS = 43200;
const CACHE_TTL_1_DAY = 86400;
const CACHE_TTL_2_DAYS = 172800;
const CACHE_TTL_3_DAYS = 259200;
const CACHE_TTL_5_DAYS = 432000;
const CACHE_TTL_1_WEEK = 604800;
const CACHE_TTL_2_WEEKS = 1209600;
const CACHE_TTL_1_MONTH = 2592000;

const CACHE_TTL_MINIMAL = 10;
const CACHE_TTL_CRITICAL = CACHE_TTL_1_MINUTE;
const CACHE_TTL_SHORT = CACHE_TTL_5_MINUTES;
const CACHE_TTL_NORMAL = CACHE_TTL_1_HOUR;
const CACHE_TTL_UNCRITICAL = CACHE_TTL_1_DAY;
const CACHE_TTL_LONG = CACHE_TTL_1_WEEK;
const CACHE_TTL_LONGEST = CACHE_TTL_1_MONTH;

/**
 * Cache
 *
 * Manages cache providers.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 *
 * @package Cherrycake
 * @category Modules
 */
class Cache extends \Cherrycake\Module {
	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Errors"
	];

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init()
	{
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		global $e;
		$e->loadCherrycakeModuleClass("Cache", "CacheProvider");

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], (isset($provider["config"]) ? $provider["config"] : null));

		return true;
	}

	/**
	 * addProvider
	 *
	 * Adds a cache provider
	 *
	 * @param string $key The key to later access the cache provider
	 * @param string $providerClassName The cache provider class name
	 * @param array $config The configuration for the cache provider
	 */
	function addProvider($key, $providerClassName, $config)
	{
		global $e;
		$e->loadCherrycakeModuleClass("Cache", $providerClassName);
		eval("\$this->".$key." = new \\Cherrycake\\Modules\\".$providerClassName."();");
		$this->$key->config($config);
	}

	/**
	 * buildCacheKey
	 *
	 * Returns a cache key to be used in caching operations, based on the provided $config.
	 * The keys built can have one of the following syntaxes:
	 * <App namespace>_[<prefix>]_<uniqueId>
	 * <App namespace>_[<prefix>]_[<specificPrefix>]_<key|encoded sql>
	 *
	 * @param $cacheKeyNamingOptions The config options to build the cache key, holds the following key-value options:
	 * "prefix": A prefix to use
	 * "uniqueId": A unique id for the cache key that will override any other specific key identifier config options
	 * "specificPrefix": A secondary prefix to prepend to provided sql or key config values
	 * "hash": A string to be hashed as the cache key instead of using "key". For example: A SQL query
	 * "key": An arbitrary key to uniquely identify the cache key
	 *
	 * @return string The final cache key
	 */
	static function buildCacheKey($cacheKeyNamingOptions) {
		global $e;
		$key = $e->getAppNamespace();

		if (isset($cacheKeyNamingOptions["prefix"]))
			$key .= "_".$cacheKeyNamingOptions["prefix"];

		if (isset($cacheKeyNamingOptions["uniqueId"]))
			return $key."_".$cacheKeyNamingOptions["uniqueId"];

		if (isset($cacheKeyNamingOptions["specificPrefix"]))
			$key .= "_".$cacheKeyNamingOptions["specificPrefix"];

		if (isset($cacheKeyNamingOptions["hash"]))
			return  $key."_".hash("md4", $cacheKeyNamingOptions["hash"]);

		return $key."_".$cacheKeyNamingOptions["key"];
	}
}