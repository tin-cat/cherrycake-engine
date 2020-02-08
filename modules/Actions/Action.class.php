<?php

/**
 * Action
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const ACTION_MODULE_TYPE_CHERRYCAKE = 0;
const ACTION_MODULE_TYPE_APP = 1;
const ACTION_MODULE_TYPE_CHERRYCAKE_UICOMPONENT = 2;
const ACTION_MODULE_TYPE_APP_UICOMPONENT = 3;

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
	 * @var int $moduleType The type of the module that will be called on this action. Actions can call methods on both Cherrycake and App modules, but also on Cherrycake and App UiComponents
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
	private $responseClass;

	/**
	 * @var Request $request The Request that triggers this Action
	 */
	public $request;

	/**
	 * @var bool $isCache Whether this action must be cached or not
	 */
	private $isCache;

	/**
	 * @var string $cacheProviderName The name of the cache provider to use when caching this action, defaults to the defaultActionCacheProviderName config key for the Actions module
	 */
	private $cacheProviderName;

	/**
	 * @var string $cachePefix The cache prefix to use when caching this action, defaults to the defaultActionCachePrefix config key for the Actions module
	 */
	private $cachePrefix;

	/**
	 * @var int $cacheTtl The TTL to use when caching this action, defaults to the defaultActionCacheTtl config key for the Actions module
	 */
	private $cacheTtl;

	/**
	 * @var bool $isSensibleToBruteForceAttacks Whether this action is sensible to brute force attacks or not. For example, an action that checks a given password and returns false if the password is incorrect. In such case, this request will sleep for some time when the password were wrong in order to discourage crackers.
	 */
	private $isSensibleToBruteForceAttacks;

	/**
	 * @var integer $timeout When set, this action must have this specific timeout.
	 */
	private $timeout = false;

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
		$this->request = isset($setup["request"]) ? $setup["request"] : false;
		$this->isCache = isset($setup["isCache"]) ? $setup["isCache"] : false;
		$this->isSensibleToBruteForceAttacks = isset($setup["isSensibleToBruteForceAttacks"]) ? $setup["isSensibleToBruteForceAttacks"] : false;
		$this->timeout = isset($setup["timeout"]) ? $setup["timeout"] : false;

		if ($this->isCache) {
			global $e;

			if (isset($setup["cacheProviderName"]))
				$this->cacheProviderName = $setup["cacheProviderName"];
			else
				$this->cacheProviderName = $e->Actions->getConfig("defaultActionCacheProviderName");

			if (isset($setup["cacheTtl"]))
				$this->cacheTtl = $setup["cacheTtl"];
			else
				$this->cacheTtl = $e->Actions->getConfig("defaultActionCacheTtl");

			if (isset($setup["cachePrefix"]))
				$this->cachePrefix = $setup["cachePrefix"];
			else
				$this->cachePrefix = $e->Actions->getConfig("defaultActionCachePrefix");
		}
	}

	/**
	 * run
	 *
	 * Executes this action by loading the corresponding module and calling the proper method. Manages the cache for this action if needed.
	 * @return boolean True if the action was productive, false otherwise.
	 */
	function run() {
		global $e;

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

		if ($this->moduleType == ACTION_MODULE_TYPE_CHERRYCAKE)
			$e->loadCherrycakeModule($this->moduleName);
		else
		if ($this->moduleType == ACTION_MODULE_TYPE_APP)
			$e->loadAppModule($this->moduleName);
		else
		if ($this->moduleType == ACTION_MODULE_TYPE_CHERRYCAKE_UICOMPONENT && $e->Ui)
			$e->Ui->addCherrycakeUiComponent($this->moduleName);
		else
		if ($this->moduleType == ACTION_MODULE_TYPE_APP_UICOMPONENT && $e->Ui)
			$e->Ui->addAppUiComponent($this->moduleName);

		if (!$this->request->securityCheck())
			return false;

		switch ($this->moduleType) {
			case ACTION_MODULE_TYPE_CHERRYCAKE:
			case ACTION_MODULE_TYPE_APP:
				if (!method_exists($e->{$this->moduleName}, $this->methodName)) {
					$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Module method ".$this->moduleName."::".$this->methodName." not found when running Action"]);
					return true;
				}
				eval("\$return = \$e->".$this->moduleName."->".$this->methodName."(\$this->request);");
				break;
			case ACTION_MODULE_TYPE_CHERRYCAKE_UICOMPONENT:
			case ACTION_MODULE_TYPE_APP_UICOMPONENT:
				if (!method_exists($e->Ui->getUiComponent($this->moduleName), $this->methodName)) {
					$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "UiComponentModule method ".$this->moduleName."::".$this->methodName." not found when running Action"]);
					return true;
				}
				eval("\$return = \$e->Ui->getUiComponent(\"".$this->moduleName."\")->".$this->methodName."(\$this->request);");
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
	 * resetCache
	 *
	 * If this action was meant to be cached, it removes it from the cache.
	 *
	 * @param array $parameterValues An optional two-dimensional array containing values for all the parameters related to the Request on this Action, including url path parameters, get/post parameters and additionalCacheKeys. If not specified, the current retrieved values will be used
	 */
	function resetCache($parameterValues = false) {
		if (!$this->isCache)
			return;

		$cacheKey = $this->request->getCacheKey($this->cachePrefix, $parameterValues);
	}

	/**
	 * debug
	 *
	 * @return string Debug info about this Action
	 */
	function debug() {
		$r = "<ul>";
		$r .= "<li><b>moduleName:</b> ".$this->moduleName."</li>";
		$r .= "<li><b>methodName:</b> ".$this->methodName."</li>";
		$r .= "<li><b>isCache:</b> ".($this->isCache ? "Yes" : "No")."</li>";
		if ($this->isCache) {
			$r .= "<li><b>cacheProviderName:</b> ".$this->cacheProviderName."</li>";
			$r .= "<li><b>cacheTtl:</b> ".$this->cacheTtl."</li>";
			$r .= "<li><b>cachePrefix:</b> ".$this->cachePrefix."</li>";
		}
		$r .= "<li><b>Request:</b> ".$this->request->debug()."</li>";
		$r .= "</ul>";
		return $r;
	}
}