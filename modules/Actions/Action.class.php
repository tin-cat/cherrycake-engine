<?php

/**
 * Action
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const ACTION_MODULE_TYPE_CORE = 0;
const ACTION_MODULE_TYPE_APP = 1;

/**
 * Action
 *
 * A class that represents an action requested to the engine. It uses a Request object. It implements Action-level cache.
 *
 * @package Cherrycake
 * @category Classes
 */
class Action {
	/**
	 * @var int $moduleType The type of the module that will be called on this action. Actions can call methods on both Code and App modules, but also on Core and App UiComponents
	 */
	private $moduleType;

	/**
	 * @var string $moduleName The name of the module that will be called for this action
	 */
	private $moduleName;

	/**
	 * @var string $methodName The name of the method within the module that will be called for this action. This method must return false if he doesn't accepts the request. It can return true or nothing if the request has been accepted.
	 */
	private $methodName;

	/**
	 * @var string $responseClass The name of the Response class this Action is expected to return
	 */
	protected $responseClass;

	/**
	 * @var Request $request The Request that triggers this Action
	 */
	public $request;

	/**
	 * @var bool $isCache Whether this action must be cached or not
	 */
	private $isCache;

	/**
	 * @var string $cacheProviderName The name of the cache provider to use when caching this action, defaults to the defaultCacheProviderName config key for the Actions module
	 */
	private $cacheProviderName;

	/**
	 * @var string $cachePefix The cache prefix to use when caching this action, defaults to the defaultCachePrefix config key for the Actions module
	 */
	private $cachePrefix;

	/**
	 * @var int $cacheTtl The TTL to use when caching this action, defaults to the defaultCacheTtl config key for the Actions module
	 */
	private $cacheTtl;

	/**
	 * @var bool $isSensibleToBruteForceAttacks Whether this action is sensible to brute force attacks or not. For example, an action that checks a given password and returns false if the password is incorrect. In such case, this request will sleep for some time when the password is wrong in order to discourage crackers.
	 */
	private $isSensibleToBruteForceAttacks;

	/**
	 * @var mixed $timeout When set, this action must have this specific timeout.
	 */
	private $timeout = false;

	/**
	 * @var boolean $isCli When set to true, this action will only be able to be executed via the command line CLI interface
	 */
	protected $isCli = false;

	/**
	 * Request
	 *
	 * Constructor factory
	 *
	 * @param string $setup The configuration for the request
	 */
	function __construct($setup) {
		$this->moduleType = isset($setup["moduleType"]) ? $setup["moduleType"] : false;
		$this->moduleName = isset($setup["moduleName"]) ? $setup["moduleName"] : false;
		$this->methodName = isset($setup["methodName"]) ? $setup["methodName"] : false;
		$this->request = isset($setup["request"]) ? $setup["request"] : new Request;
		$this->isCache = isset($setup["isCache"]) ? $setup["isCache"] : false;
		$this->isSensibleToBruteForceAttacks = isset($setup["isSensibleToBruteForceAttacks"]) ? $setup["isSensibleToBruteForceAttacks"] : false;
		$this->timeout = isset($setup["timeout"]) ? $setup["timeout"] : false;

		if ($this->isCache) {
			global $e;

			if (isset($setup["cacheProviderName"]))
				$this->cacheProviderName = $setup["cacheProviderName"];
			else
				$this->cacheProviderName = $e->Actions->getConfig("defaultCacheProviderName");

			if (isset($setup["cacheTtl"]))
				$this->cacheTtl = $setup["cacheTtl"];
			else
				$this->cacheTtl = $e->Actions->getConfig("defaultCacheTtl");

			if (isset($setup["cachePrefix"]))
				$this->cachePrefix = $setup["cachePrefix"];
			else
				$this->cachePrefix = $e->Actions->getConfig("defaultCachePrefix");
		}
	}

	/**
	 * @return boolean Whether this Action is intended for a command line request or not
	 */
	function isCli() {
		return $this->isCli;
	}

	/**
	 * run
	 *
	 * Executes this action by loading the corresponding module and calling the proper method. Manages the cache for this action if needed.
	 * @return boolean True if the action was productive, false otherwise.
	 */
	function run() {
		global $e;

		if ($this->isCli && !$e->isCli()) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "This action only runs on the CLI interface"]);
			return true;
		}

		if ($this->isCache) {
			$cacheKey = $this->request->getCacheKey($this->cachePrefix);

			// Retrieve and return the cached action results, if there are any
			if ($cached = $e->Cache->{$this->cacheProviderName}->get($cacheKey)) {
				$e->Output->setResponse(unserialize(substr($cached, 1)));
				return $cached[0] == 0 ? false : null;
			}
		}

		if ($this->timeout)
			set_time_limit($this->timeout);

		if ($this->moduleType == ACTION_MODULE_TYPE_CORE)
			$e->loadCoreModule($this->moduleName);
		else
		if ($this->moduleType == ACTION_MODULE_TYPE_APP)
			$e->loadAppModule($this->moduleName);

		if (!$this->request->securityCheck())
			return false;

		switch ($this->moduleType) {
			case ACTION_MODULE_TYPE_CORE:
			case ACTION_MODULE_TYPE_APP:
				if (!method_exists($e->{$this->moduleName}, $this->methodName)) {
					$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, ["errorDescription" => "Mapped method ".$this->moduleName."::".$this->methodName." not found"]);
					return true;
				}
				eval("\$return = \$e->".$this->moduleName."->".$this->methodName."(\$this->request);");
				break;
		}

		if ($this->isCache) {
			// Store the current result into cache
			$e->Cache->{$this->cacheProviderName}->set(
				$cacheKey,
				($return === false ? "0" : "1").serialize($e->Output->getResponse()),
				$this->cacheTtl
			);
		}

		if ($this->isSensibleToBruteForceAttacks && $return == false)
			sleep(rand(
				$e->Actions->getConfig("sleepSecondsWhenActionSensibleToBruteForceAttacksFails")[0],
				$e->Actions->getConfig("sleepSecondsWhenActionSensibleToBruteForceAttacksFails")[1]
			));

		return $return;
	}

	/**
	 * clearCache
	 *
	 * If this action was meant to be cached, it removes it from the cache.
	 *
	 * @param array $parameterValues An optional hash array containing the values for the variable path components, parameters and additionalCacheKeys involved in this action's Request. If not specified, the current parameter values will be used.
	 */
	function clearCache($parameterValues = false) {
		if (!$this->isCache)
			return;

		$cacheKey = $this->request->getCacheKey($this->cachePrefix, $parameterValues);
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		$r = [
			"brief" => $this->moduleName."::".$this->methodName." ".$this->request->getStatus()["brief"],
			"moduleName" => $this->moduleName,
			"methodName" => $this->methodName,
			"isCache" => $this->isCache
		];
		if ($this->isCache) {
			$r["cacheProviderName"] = $this->cacheProviderName;
			$r["cacheTtl"] = $this->cacheTtl;
			$r["cachePrefix"] = $this->cachePrefix;
		}
		$r["request"] = $this->request->getStatus();
		return $r;
	}
}