<?php

/**
 * Actions
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * Actions
 *
 * Module to manage the queries to the engine. It answers to queries by evaluating the query path and finding a matching mapped Action. Methods running via mapped actions must return false if they don't accept the request in order to let other methods in other mapped actions have a chance of accepting it. They must return true or nothing if they accept the request.
 * It takes configuration from the App-layer configuration file.
 *
 * Configuration example for actions.config.php:
 * <code>
 * $actionsConfig = [
 * 	"actionableModules" => [ // The actionable modules, modules that are allowed to be requested via an http query to the engine. A hash array in the form of <primaryAction> => <module name>
 * 		ACTION_DEFAULT => "Home"
 * 	],
 * 	"cache" => [
 * 		"provider" => "huge" // The default cache provider to use
 * 	],
 *	"sleepSecondsWhenActionSensibleToBruteForceAttacksFails" => [0, 3] // An array containing two values: the minimum and maximum seconds to wait after an action that was marked as sensible to brute force attacks has been executed and returned false, to discourage crackers. A random number of seconds between the minimum and maximum specified will be used for added confusion.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Actions extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"defaultActionCacheTtl" => \Cherrycake\Modules\CACHE_TTL_NORMAL,
		"sleepSecondsWhenActionSensibleToBruteForceAttacksFails" => [0, 3]
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
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
	public $actions;

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
	 * init
	 *
	 * Initializes the module
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		
		if (!parent::init())
			return false;

		global $e;
		$e->loadCherrycakeModuleClass("Actions", "Action");
		$e->loadCherrycakeModuleClass("Actions", "Request");
		$e->loadCherrycakeModuleClass("Actions", "RequestPathComponent");
		$e->loadCherrycakeModuleClass("Actions", "RequestParameter");

		$actionableCherrycakeModuleNames = $this->getConfig("actionableCherrycakeModuleNames");
		if (is_array($actionableCherrycakeModuleNames))
			foreach ($actionableCherrycakeModuleNames as $actionableCherrycakeModuleName) {
				$e->includeModuleClass(LIB_DIR."/modules", $actionableCherrycakeModuleName);
				forward_static_call(["\\Cherrycake\\Modules\\".$actionableCherrycakeModuleName, "mapActions"]);
			}

		$actionableAppModuleNames = $this->getConfig("actionableAppModuleNames");
		if (is_array($actionableAppModuleNames))
			foreach ($actionableAppModuleNames as $actionableAppModuleName) {
				$e->includeModuleClass(\Cherrycake\APP_MODULES_DIR, $actionableAppModuleName);
				forward_static_call(["\\".$e->getAppNamespace()."\\Modules\\".$actionableAppModuleName, "mapActions"]);
			}

		return true;
	}

	/**
	 * mapAction
	 *
	 * Maps an action for a module (either an App or a Cherrycake module)
	 *
	 * @param $actionName string The action name
	 * @param $action Action object
	 */
	public function mapAction($actionName, $action) {
		$this->actions[$actionName] = $action;
	}

	/**
	 * isAction
	 *
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
	 * getAction
	 *
	 * @param $actionName string The action name
	 * @return Action The requested action. False if doesn't exists.
	*/
	public function getAction($actionName) {
		if (!$this->isAction($actionName))
			return false;

		return $this->actions[$actionName];
	}

	/**
	 * run
	 *
	 * Parses the received query to find the corresponding action and runs it
	 *
	 * @param string $requestUri The request URI to run.
	 * @return bool Returns false if an error occurred when executing the action or if the requested action is not coded and ACTION_NOT_FOUND has not been mapped.
	 */
	function run($requestUri) {
		global $e;

		// Check the currentRequestPath against all mapped actions
		$currentAction = false;
		$matchingActions = false;
		if (is_array($this->actions)) {
			$this->buildCurrentRequestPathComponentStringsFromRequestUri($requestUri);
			// Loop through all mapped actions
			while (list($actionName, $action) = each($this->actions))
				if ($action->request->isCurrentRequest())
					$matchingActions[$actionName] = $action;
			reset($this->actions);
		}

		if (!$matchingActions) {
			$this->notFound();
			return false;
		}

		while (list($actionName, $action) = each($matchingActions)) {
			if (!$action->request->retrieveParameterValues())
				continue;
			$this->currentActionName = $actionName;
			$this->currentAction = $action;
			if ($action->run() === false)
				continue;
			else
				return;
		}

		$this->notFound();
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
	 * notFound
	 *
	 * Reacts to a not found error action
	 */
	function notFound() {
		global $e;
		$e->Errors->trigger(\Cherrycake\Modules\ERROR_NOT_FOUND);
	}

	/**
	 * debug
	 *
	 * @return string Debug information about the configured actions
	 */
	function debug() {
		$r = "";
		if ($actionableCherrycakeModuleNames = $this->getConfig("actionableCherrycakeModuleNames")) {
			$r .= "<b>Actionable Cherrycake modules</b><ul>";
			foreach ($actionableCherrycakeModuleNames as $actionableCherrycakeModuleName)
				$r .= "<li>".$actionableCherrycakeModuleName."</li>";
			$r .= "</ul>";
		}

		if ($actionableAppModuleNames = $this->getConfig("actionableAppModuleNames")) {
			$r .= "<b>Actionable App modules</b><ul>";
			foreach ($actionableAppModuleNames as $actionableAppModuleName)
				$r .= "<li>".$actionableAppModuleName."</li>";
			$r .= "</ul>";
		}

		if (is_array($this->actions)) {
			$r .= "<b>Mapped actions</b><br>";
			while (list($actionName, $action) = each($this->actions))
				$r .= "<b>Action name:</b> ".$actionName."<ul>".$action->debug()."</ul>";
			reset($this->actions);
		}

		return $r;
	}

}