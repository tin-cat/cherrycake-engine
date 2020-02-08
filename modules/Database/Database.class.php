<?php

/**
 * Database
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Database
 *
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
 *
 * @package Cherrycake
 * @category Modules
 */
class Database extends \Cherrycake\Module
{
	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Errors",
		"Cache"
	];

	/**
	 * init
	 *
	 * Initializes the module and loads the base CacheProvider class
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		global $e;
		$e->loadCherrycakeModuleClass("Database", "DatabaseProvider");
		$e->loadCherrycakeModuleClass("Database", "DatabaseResult");
		$e->loadCherrycakeModuleClass("Database", "DatabaseRow");

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"]);

		return true;
	}

	/**
	 * addProvider
	 *
	 * Adds a database provider
	 *
	 * @param string $key The key to later access the database provider
	 * @param string $providerClassName The database provider class name
	 * @param array $config The configuration for the database provider
	 */
	function addProvider($key, $providerClassName, $config) {
		global $e;
		$e->loadCherrycakeModuleClass("Database", $providerClassName);

		eval("\$this->".$key." = new \\Cherrycake\\Modules\\".$providerClassName."();");

		$this->$key->config($config);

		if (!$this->$key->init()) {
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Error while Initting database provider"]);
			return;
		}
	}
}