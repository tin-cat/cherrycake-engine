<?php

namespace Cherrycake\Modules\Actions;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Errors\Errors;

/**
 * A class that represents an action requested to the engine. It uses a Request object. It implements Action-level cache.
 */
class Action {
	/**
	 * @param int $moduleType The type of the module that will be called on this action. Actions can call methods on both Core and App modules
	 * @param string $moduleName The name of the module that will be called for this action
	 * @param string $methodName The name of the method within the module that will be called for this action. This method must return false if he doesn't accepts the request. It can return true or nothing if the request has been accepted.
	 * @param string $responseClass The name of the Response class this Action is expected to return
	 * @param Request $request The Request that triggers this Action
	 * @param bool $isCache Whether this action must be cached or not
	 * @param string $cacheProviderName The name of the cache provider to use when caching this action, defaults to the defaultCacheProviderName config key for the Actions module
	 * @param string $cachePefix The cache prefix to use when caching this action, defaults to the defaultCachePrefix config key for the Actions module
	 * @param int $cacheTtl The TTL to use when caching this action, defaults to the defaultCacheTtl config key for the Actions module
	 * @param bool $isSensibleToBruteForceAttacks Whether this action is sensible to brute force attacks or not. For example, an action that checks a given password and returns false if the password is incorrect. In such case, this request will sleep for some time when the password is wrong in order to discourage crackers.
	 * @param mixed $timeout When set, this action must have this specific timeout
	 * @param boolean $isCli When set to true, this action will only be able to be executed via the command line CLI interface
	 * @param boolean $isRequireLogin When set to true, if the user is not logged in, it will be required to login to access this action
	 * @param boolean $isFailOnNotLogged When set to true, if the user is not logged in, an ERROR_NO_PERMISSION will be triggered
	 */
	function __construct(
		private string $moduleName,
		private string $methodName,
		public Request $request,
		private int $moduleType = \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_APP,
		protected string $responseClass = '',
		private bool $isCache = false,
		private string $cacheProviderName = '',
		private string $cachePrefix = '',
		private int $cacheTtl = 0,
		private bool $isSensibleToBruteForceAttacks = false,
		private int $timeout = 0,
		private bool $isCli = false,
		private bool $isRequireLogin = false,
		private bool $isFailOnNotLogged = false,
	) {
		if (!$this->request)
			$this->request = new Request;

		if (!$this->cacheProviderName)
			$this->cacheProviderName = Engine::e()->Actions->getConfig("defaultCacheProviderName");

		if (!$this->cacheTtl)
			$this->cacheTtl = Engine::e()->Actions->getConfig("defaultCacheTtl");

		if (!$this->cachePrefix)
			$this->cachePrefix = Engine::e()->Actions->getConfig("defaultCachePrefix");
	}

	/**
	 * @return boolean Whether this Action is intended for a command line request or not
	 */
	function isCli(): bool {
		return $this->isCli;
	}

	/**
	 * Executes this action by loading the corresponding module and calling the proper method. Manages the cache for this action if needed.
	 * @return boolean True if the action was productive, false otherwise.
	 */
	function run() {
		if ($this->isCli && !Engine::e()->isCli()) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_SYSTEM,
				description: "This action only runs on the CLI interface"
			);
			return true;
		}

		if ($this->isRequireLogin && !Engine::e()->Login->isLogged()) {
			Engine::e()->Output->setResponse(new \Cherrycake\Modules\Actions\Response(
				code: \Cherrycake\Modules\Output\Output::RESPONSE_REDIRECT_FOUND,
				url: Engine::e()->Actions->getAction(
					Engine::e()->Actions->getConfig('loginActionName')
				)->request->buildUrl()
			));
			return true;
		}

		if ($this->isFailOnNotLogged && !Engine::e()->Login->isLogged()) {
			Engine::e()->Errors->trigger(
				type: Errors::ERROR_NO_PERMISSION
			);
			return true;
		}

		if ($this->isCache) {
			$cacheKey = $this->request->getCacheKey($this->cachePrefix);

			// Retrieve and return the cached action results, if there are any
			if ($cached = Engine::e()->Cache->{$this->cacheProviderName}->get($cacheKey)) {
				Engine::e()->Output->setResponse(unserialize(substr($cached, 1)));
				return $cached[0] == 0 ? false : null;
			}
		}

		if ($this->timeout)
			set_time_limit($this->timeout);

		if ($this->moduleType == \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_CORE)
			Engine::e()->loadCoreModule($this->moduleName);
		else
		if ($this->moduleType == \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_APP)
			Engine::e()->loadAppModule($this->moduleName);

		if (!$this->request->securityCheck())
			return false;

		switch ($this->moduleType) {
			case \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_CORE:
			case \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_APP:
				if (!method_exists(Engine::e()->{$this->moduleName}, $this->methodName)) {
					Engine::e()->Errors->trigger(
						type: Errors::ERROR_SYSTEM,
						description: "Mapped method ".$this->moduleName."::".$this->methodName." not found"
					);
					return true;
				}
				eval("\$return = \Cherrycake\Classes\Engine::e()->".$this->moduleName."->".$this->methodName."(\$this->request);");
				break;
		}

		if ($this->isCache) {
			// Store the current result into cache
			Engine::e()->Cache->{$this->cacheProviderName}->set(
				$cacheKey,
				($return === false ? "0" : "1").serialize(Engine::e()->Output->getResponse()),
				$this->cacheTtl
			);
		}

		if ($this->isSensibleToBruteForceAttacks && $return == false)
			sleep(rand(
				Engine::e()->Actions->getConfig("sleepSecondsWhenActionSensibleToBruteForceAttacksFails")[0],
				Engine::e()->Actions->getConfig("sleepSecondsWhenActionSensibleToBruteForceAttacksFails")[1]
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
