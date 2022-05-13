<?php

namespace Cherrycake\Modules\Database;

/**
 * Manages database providers.
 * It takes configuration from the App-layer configuration file.
 * This module and its submodules are intended to be fast, reliable and low-memory consuming. To use it in a proper way and to get all the benefits of optimization, take care of the following when using it:
 *
 * * Results from queries are always stored on memory and database is released from them as soon as the data is retrieved.
 * * Because of the above, avoid performing queries containing data that will not be used. I.e: Filter the queried rows in the sql, not in the code. Request only the needed fields.
 *
 * Configuration example for database.config.php:
 * <code>
 * $databaseConfig = [
 * 	"providers" => [
 * 		"main" => [
 * 			"providerClassName" => "DatabaseProviderMysql",
 * 			"resultClassName" => "DatabaseResultMysql",
 * 			"config" => [
 * 				"host" => "127.0.0.1",
 * 				"user" => "test",
 * 				"password" => "ddXP63dLKPV3Jz8H",
 * 				"database" => "test",
 * 				"cacheProviderName" => "huge"
 * 			]
 * 		]
 * 	]
 * ];
 * </code>
 */
class Database extends \Cherrycake\Module {

	const TYPE_INTEGER = 0;
	const TYPE_TINYINT = 1;
	const TYPE_FLOAT = 2;
	const TYPE_DATE = 3;
	const TYPE_DATETIME = 4;
	const TYPE_TIMESTAMP = 5;
	const TYPE_TIME = 6;
	const TYPE_YEAR = 7;
	const TYPE_STRING = 8;
	const TYPE_TEXT = 9;
	const TYPE_BLOB = 10;
	const TYPE_BOOLEAN = 11;
	const TYPE_IP = 12;
	const TYPE_SERIALIZED = 13;
	const TYPE_COLOR = 14;

	const DEFAULT_VALUE = 0;
	const DEFAULT_VALUE_DATE = 1;
	const DEFAULT_VALUE_DATETIME = 2;
	const DEFAULT_VALUE_TIMESTAMP = 3;
	const DEFAULT_VALUE_TIME = 4;
	const DEFAULT_VALUE_YEAR = 5;
	const DEFAULT_VALUE_IP = 6;
	const DEFAULT_VALUE_AVAILABLE_URL_SHORT_CODE = 7;

	/**
	 * @var bool $isConfigFileRequired Whether the config file for this module is required to run the app
	 */
	protected bool $isConfigFileRequired = true;

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Cache"
	];

	/**
	 * Initializes the module and loads the base CacheProvider class
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"]);

		return true;
	}

	/**
	 * Adds a database provider
	 * @param string $key The key to later access the database provider
	 * @param string $providerClassName The database provider class name
	 * @param array $config The configuration for the database provider
	 */
	function addProvider(string $key, string $providerClassName, array $config) {
		eval("\$this->".$key." = new \\Cherrycake\\Modules\\Database\\".$providerClassName."();");

		$this->$key->config($config);

		// if (!$this->$key->init()) {
		// 	Engine::e()->Errors->trigger(type: Errors::ERROR_SYSTEM, description:  "Error while Initting database provider");
		// 	return;
		// }
	}
}
