<?php

namespace Cherrycake\Actions;

/**
 * Module to manage the queries to the engine. It answers to queries by evaluating the query path and finding a matching mapped Action. Methods running via mapped actions must return false if they don't accept the request in order to let other methods in other mapped actions have a chance of accepting it. They must return true or nothing if they accept the request.
 * It takes configuration from the App-layer configuration file.
 *
 * @package Cherrycake
 * @category Modules
 */
class Actions extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"defaultCacheProviderName" => "engine", // The default cache provider name to use.
		"defaultCacheTtl" => \Cherrycake\CACHE_TTL_NORMAL, // De default TTL to use.
		"defaultCachePrefix" => "Actions",
		"sleepSecondsWhenActionSensibleToBruteForceAttacksFails" => [0, 3] // An array containing the minimum and maximum number of seconds to wait when an action marked as sensible to brute force attacks has been executed and failed.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		"Output",
		"Errors",
		"Security"
	];

	/**
	 * @var Request $request Holds the current request
	 */
	var $request;

	/**
	 * @var array $actions An array of Actions to be handled by this module
	 */
	private $actions;

	/**
	 * @var array $currentRequestPathComponentStrings An array of strings representing the path of the currently made request, built on Actions::buildCurrentRequestPathComponentStringsFromRequestUri
	 */
	public $currentRequestPathComponentStrings = false;

	/**
	 * @var Action $currentAction The current Action being executed
	 */
	public $currentAction;

	/**
	 * @var string $currentActionName The name of the  current Action being executed
	 */
	public $currentActionName;

	/**
	 * Initializes the module
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;

		global $e;
		$e->callMethodOnAllModules("mapActions");

		return true;
	}

	/**
	 * Maps an action for a module (either an App or a Core module). Should be called within the mapActions method of your module, like this:
	 *
	 * $e->Actions->mapAction(
	 * 	"TableAdminGetRows",
	 * 	new \Cherrycake\ActionHtml([
	 * 		"moduleType" => ACTION_MODULE_TYPE_CORE,
	 * 		"moduleName" => "TableAdmin",
	 * 		"methodName" => "getRows",
	 * 		"request" => new \Cherrycake\Request([
	 * 			"isSecurityCsrf" => true,
	 * 			"pathComponents" => [
	 * 				new \Cherrycake\RequestPathComponent([
	 * 					"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
	 * 					"string" => "TableAdmin"
	 * 				]),
	 * 				new \Cherrycake\RequestPathComponent([
	 * 					"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING,
	 * 					"name" => "mapName",
	 * 					"securityRules" => [
	 * 						SECURITY_RULE_NOT_EMPTY,
	 * 						SECURITY_RULE_SLUG
	 * 					]
	 * 				]),
	 * 				new \Cherrycake\RequestPathComponent([
	 * 					"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
	 * 					"string" => "getRows"
	 * 				])
	 * 			],
	 * 			"parameters" => [
	 * 				new \Cherrycake\RequestParameter([
	 * 					"name" => "additionalFillFromParameters",
	 * 					"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
	 * 				])
	 * 			]
	 * 		])
	 * 	])
	 * );
	 *
	 * @param $actionName string The action name
	 * @param $action Action object
	 */
	public function mapAction($actionName, $action) {
		$this->actions[$actionName] = $action;
	}

	/**
	 * Checks if an action with the given actionName has been set
	 *
	 * @param $actionName string The action name
	 * @return bool True if the action exists, false if doesnt's.
	*/
	public function isAction($actionName) {
		if (!is_array($this->actions))
			return false;

		return array_key_exists($actionName, $this->actions);
	}

	/**
	 * @param $actionName string The action name
	 * @return Action The requested action. False if doesn't exists.
	*/
	public function getAction($actionName) {
		if (!$this->isAction($actionName))
			return false;

		return $this->actions[$actionName];
	}

	/**
	 * Parses the received query to find the corresponding action and runs it
	 * @param string $requestUri The request URI to run.
	 * @return bool Returns false if an error occurred when executing the action or if the requested action is not coded and ACTION_NOT_FOUND has not been mapped.
	 */
	function run($requestUri) {
		global $e;

		if ($e->isDevel() && !is_array($this->actions)) {
			$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
				"errorDescription" => "No mapped actions"
			]);
		}

		// Check the currentRequestPath against all mapped actions
		$matchingActions = false;
		if (is_array($this->actions)) {
			$this->buildCurrentRequestPathComponentStringsFromRequestUri($requestUri);
			// Loop through all mapped actions
			foreach ($this->actions as $actionName => $action) {
				if (!$action->isCli() && $action->request->isCurrentRequest())
					$matchingActions[$actionName] = $action;
			}
			reset($this->actions);
		}

		if (!$matchingActions) {
			$e->Errors->trigger(\Cherrycake\ERROR_NOT_FOUND, [
				"errorDescription" => "No mapped action found for this request"
			]);
			return false;
		}

		foreach ($matchingActions as $actionName => $action) {
			$this->currentActionName = $actionName;
			$this->currentAction = $action;
			if (!$action->request->retrieveParameterValues())
				continue;
			if ($action->run() === false) {
				$nonproductiveMatchingActions[] = $actionName;
				continue;
			}
			else
				return;
		}

		$e->Errors->trigger(\Cherrycake\ERROR_NOT_FOUND, [
			"errorDescription" => "No matching actions were productive",
			"errorVariables" => [
				"nonproductiveMatchingActions" => $nonproductiveMatchingActions
			]
		]);
	}

	/**
	 * Builds the $currentRequestPathComponentStrings array, to be used lately by Request::isCurrentRequest
	 * @param string $requestUri The URI string to build the currentRequestPathComponentStrings from
	 */
	function buildCurrentRequestPathComponentStringsFromRequestUri($requestUri) {
		// Strip get parameters
		if ($firstInterrogantPosition = strpos($requestUri, "?"))
			$requestUri = substr($requestUri, 0, $firstInterrogantPosition);

		// Strip first slash if present
		if (substr($requestUri, 0, 1) == "/")
			$requestUri = substr($requestUri, 1);

		// Strip trailing slash if present
		if (substr($requestUri, strlen($requestUri)-1, 1) == "/")
			$requestUri = substr($requestUri, 0, strlen($requestUri)-1);

		if ($requestUri)
			$this->currentRequestPathComponentStrings = explode("/", $requestUri);
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		if (is_array($this->actions)) {
			foreach ($this->actions as $actionName => $action) {
				$r["mappedActions"][$actionName] = $action->getStatus();
				$r["brief"]["mappedActions"][$actionName] = $action->getStatus()["brief"];
			}
			reset($this->actions);
		}

		return $r ?? null;
	}

}
