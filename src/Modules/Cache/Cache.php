<?php

namespace Cherrycake\Modules\Cache;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Errors\Errors;

/**
 * Manages cache providers.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 * For many processes like log committing via janitor tasks, ensure that `appName` are equal both in `index.php` and in `cli.php`
 */
class Cache extends \Cherrycake\Classes\Module {

	const TTL_1_MINUTE = 60;
	const TTL_5_MINUTES = 300;
	const TTL_10_MINUTES = 600;
	const TTL_30_MINUTES = 1800;
	const TTL_1_HOUR = 3600;
	const TTL_2_HOURS = 7200;
	const TTL_6_HOURS = 21600;
	const TTL_12_HOURS = 43200;
	const TTL_1_DAY = 86400;
	const TTL_2_DAYS = 172800;
	const TTL_3_DAYS = 259200;
	const TTL_5_DAYS = 432000;
	const TTL_1_WEEK = 604800;
	const TTL_2_WEEKS = 1209600;
	const TTL_1_MONTH = 2592000;

	const TTL_MINIMAL = 10;
	const TTL_CRITICAL = self::TTL_1_MINUTE;
	const TTL_SHORT = self::TTL_5_MINUTES;
	const TTL_NORMAL = self::TTL_1_HOUR;
	const TTL_UNCRITICAL = self::TTL_1_DAY;
	const TTL_LONG = self::TTL_1_WEEK;
	const TTL_LONGEST = self::TTL_1_MONTH;

	/**
	 * @var bool $isConfigFileRequired Whether the config file for this module is required to run the app
	 */
	protected bool $isConfigFileRequired = false;

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		// Check that the "engine" cache provider has not been defined previously
		if (Engine::e()->isDevel() && isset($this->getConfig("providers")["engine"])) {
			Engine::e()->loadCoreModule("Errors");
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "The \"engine\" cache provider name is reserved"
			);
		}

		// Setup the engine cache
		$this->config["providers"]["engine"] = [
			"providerClassName" => "CacheProviderApcu"
		];

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"] ?? []);

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
	function addProvider(
		string $key,
		string $providerClassName,
		?array $config,
	) {
		eval("\$this->".$key." = new \\Cherrycake\\Modules\\Cache\\".$providerClassName."();");
		$this->$key->config($config);
	}

	/**
	 * Returns a cache key to be used in caching operations, based on the provided $config.
	 * The keys built can have one of the following syntaxes:
	 * <App namespace>_[<prefix>]_<uniqueId>
	 * <App namespace>_[<prefix>]_[<specificPrefix>]_<key|encoded sql>
	 *
	 * @param string $prefix A prefix
	 * @param string $uniqueId A unique id for the cache key that will override any other specific key identifier config options
	 * @param string $specificPrefix A secondary prefix to prepend to provided sql or key config values
	 * @param string $hash A string to be hashed as the cache key instead of using "key". For example: A SQL query
	 * @param string $key An arbitrary key to uniquely identify the cache key
	 * @return string The cache key
	 */
	static function buildCacheKey(
		?string $prefix = null,
		?string $uniqueId = null,
		?string $specificPrefix = null,
		?string $hash = null,
		?string $key = null
	) {
		$r = Engine::e()->getAppName();

		if (isset($prefix))
			$r .= "_".$prefix;

		if (isset($uniqueId))
			return $r."_".$uniqueId;

		if (isset($specificPrefix))
			$r .= "_".$specificPrefix;

		if (isset($hash))
			return  $r."_".hash("md5", $hash);

		return $r."_".$key;
	}
}
