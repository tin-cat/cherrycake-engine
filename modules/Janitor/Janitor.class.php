<?php

/**
 * Janitor
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const JANITORTASK_EXECUTION_RETURN_WARNING = 0; // Return code for JanitorTask run when task returned an error
const JANITORTASK_EXECUTION_RETURN_ERROR = 1; // Return code for JanitorTask run when task returned an error
const JANITORTASK_EXECUTION_RETURN_CRITICAL = 2; // Return code for JanitorTask run when task returned an error
const JANITORTASK_EXECUTION_RETURN_OK = 3; // Return code for JanitorTask run when task was executed without errors

const JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL = 0; // Task can only be executed when calling the Janitor run process with an specific task parameter. It won't be executed on regular "all-tasks" calls to Janitor
const JANITORTASK_EXECUTION_PERIODICITY_ALWAYS = 1; // Task must be executed everytime Janitor run is called
const JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS = 2; // Task must be executed every specified seconds. Seconds specified in "periodicityEachSeconds" config key
const JANITORTASK_EXECUTION_PERIODICITY_MINUTES = 3; // Task must be executed on the given minutes of each hour. Desired minutes are specified as an array in the "periodicityMinutes" config key with the syntax: [0, 15, 30, 45]
const JANITORTASK_EXECUTION_PERIODICITY_HOURS = 4; // The task must be executed on the given hours of each day. Desired hours/minute are specified as an array in the "periodicityHours" config key with the syntax: ["hour:minute", "hour:minute", "hour:minute"]
const JANITORTASK_EXECUTION_PERIODICITY_DAYSOFMONTH = 5; // The task must be executed on the given days of each month. Desired days/hour/minute are specified as an array in the "periodicityDaysOfMonth" config key with the syntax: ["day@hour:minute", "day@hour:minute", "day@hour:minute"] (Take into account days of month that do not exist)

/**
 * Janitor
 *
 * Executes maintenance tasks and checks.
 *
 * It adds two actions:
 *  /janitor/run
 *      Runs all the tasks that must be run at the time of request.
 *      Needs the "key" GET parameter.
 *      Can receive the "task" GET parameter with the name of a task to be individually executed. It considers all configured tasks if not specified.
 *
 *  /janitor/status
 *      Presents a page with a tasks report status
 *      Needs the "key" GET parameter.
 *
 * Configuration example for Janitor.config.php:
 * <code>
 * $janitorConfig = [
 * 	"key" => false, // The key string needed to run janitor tasks and to access the status page. Must be overloaded by janitor.config.php
 *  "logDatabaseProviderName" => "main", // The name of the DatabaseProvider to use for storing Janitor log
 *  "logTableName" => "cherrycake_janitor_log", // The name of the table used to store Janitor log
 *  "cherrycakeJanitorTasks" => [ // An array of names of Cherrycake JanitorTask classes to be run
 *  ],
 *  "appJanitorTasks" => [ // An array of names of App JanitorTask classes to be run
 *  ]
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Janitor extends \Cherrycake\Module {
	/**
	 * @var array $config Holds the default configuration for this module
	 */
	protected $config = [
		"key" => false,
		"logTableName" => "cherrycake_janitor_log"
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Errors",
		"Database"
	];

	/**
	 * var @array $janitorTasks The array of JanitorTask objects that have been added to Janitor
	 */
	var $janitorTasks;

	/**
	 * init
	 *
	 * Initializes the module and loads the Ui components
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		return true;
	}

	/**
	 * mapActions
	 *
	 * Maps the Actions to which this module must respond
	 */
	public static function mapActions() {
		global $e;

		$e->Actions->mapAction(
			"janitorRun",
			new \Cherrycake\ActionPlainText([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
				"moduleName" => "Janitor",
				"methodName" => "run",
				"request" => new \Cherrycake\Request([
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "janitor"
						]),
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "run"
						])
					],
					"parameters" => [
						new \Cherrycake\RequestParameter([
							"name" => "key",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET,
							"securityRules" => [
								\Cherrycake\SECURITY_RULE_NOT_EMPTY
							]
						]),
						new \Cherrycake\RequestParameter([
							"name" => "task",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
						]),
						new \Cherrycake\RequestParameter([
							"name" => "isForceRun",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
						])
					]
				])
			])
		);

		$e->Actions->mapAction(
			"janitorStatus",
			new \Cherrycake\ActionHtml([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
				"moduleName" => "Janitor",
				"methodName" => "status",
				"request" => new \Cherrycake\Request([
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "janitor"
						]),
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "status"
						])
					],
					"parameters" => [
						new \Cherrycake\RequestParameter([
							"name" => "key",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET,
							"securityRules" => [
								\Cherrycake\SECURITY_RULE_NOT_EMPTY
							]
						])
					]
				])
			])
		);
	}

	/**
	 * loadTasks
	 *
	 * Loads the configured tasks
	 */
	function loadTasks() {
		if (is_array($this->janitorTasks))
			return;

		global $e;
		$e->loadCherrycakeModuleClass("Janitor", "JanitorTask");

		// Sets up Janitor tasks
		if (is_array($cherrycakeJanitorTasks = $this->getConfig("cherrycakeJanitorTasks")))
			foreach($cherrycakeJanitorTasks as $cherrycakeJanitorTask)
				$this->addCherrycakeJanitorTask($cherrycakeJanitorTask);

		if (is_array($appJanitorTasks = $this->getConfig("appJanitorTasks")))
			foreach($appJanitorTasks as $appJanitorTask)
				$this->addAppJanitorTask($appJanitorTask);
	}

	/**
	 * addCherrycakeJanitorTask
	 *
	 * Adds a Cherrycake Janitor task
	 *
	 * @param string $janitorTaskName The name of the class of the Cherrycake Janitor task to add
	 */
	function addCherrycakeJanitorTask($janitorTaskName) {
		global $e;

		if (!isset($this->janitorTasks[$janitorTaskName])) {
			$e->loadCherrycakeModuleClass("Janitor", $janitorTaskName);
			eval("\$this->janitorTasks[\"".$janitorTaskName."\"] = new \\Cherrycake\\".$janitorTaskName."();");
			$this->janitorTasks[$janitorTaskName]->init();
		}
	}

	/**
	 * addAppJanitorTask
	 *
	 * Adds an App Janitor task
	 *
	 * @param string $janitorTaskName The name of the class of the App Janitor task to add
	 */
	function addAppJanitorTask($janitorTaskName) {
		global $e;

		if (!isset($this->janitorTasks[$janitorTaskName])) {
			$e->loadAppModuleClass("Janitor", $janitorTaskName);
			eval("\$this->janitorTasks[\"".$janitorTaskName."\"] = new \\".$e->getAppNamespace()."\\".$janitorTaskName."();");
			$this->janitorTasks[$janitorTaskName]->init();
		}
	}

	/**
	 * Checks whether the task with the given task name exists
	 * @param string $janitorTaskName The task name
	 * @return boolean True if a task with the specified name exists, false otherwise
	 */
	function isTask($janitorTaskName) {
		$this->loadTasks();
		return isset($this->janitorTasks[$janitorTaskName]);
	}

	/**
	 * checkKey
	 *
	 * Checks if the given key allows access to Janitor
	 *
	 * @param string $key The key to check
	 * @return bool Whether the key is correct or not
	 */
	function checkKey($key) {	
		if ($this->getConfig("key") === false || $key != $this->getConfig("key")) {
			global $e;
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Wrong Janitor key provided"]);
			return false;
		}
		return true;
	}

	/**
	 * run
	 *
	 * Runs Janitor to determine which tasks need to be executed now, and executes them.
	 */
	function run($request) {
		if (!$this->checkKey($request->key))
			return false;

		$task = $request->task;
		$isForceRun = $request->isForceRun;

		$this->loadTasks();
		
		global $e;
		$r = "Janitor run\n";

		$baseTimestamp = time();

		$r .= "Task to be considered: ".($task ? $task : "All")."\n";

		$r .= "Base timestamp: ".date("j/n/Y H:i:s", $baseTimestamp)."\n";

		if ($task && !$this->isTask($task)) {
			$r .= "Specified task does not exists";
			return false;
		}

		foreach ($this->janitorTasks as $janitorTaskName => $janitorTask) {
			if ($task && $task != $janitorTaskName)
				continue;

			$r .= " . ".$janitorTaskName." [".$janitorTask->getPeriodicityDebugInfo()."] ";

			if ($janitorTask->isToBeExecuted($baseTimestamp) || $isForceRun) {
				if ($isForceRun)
					$r .= "Forcing execution. ";
				$r .= "Executing: ";
				$microtimeStart = microtime(true);
				list($resultCode, $resultDescription) = $janitorTask->run($baseTimestamp);
				$executionSeconds = (microtime(true) - $microtimeStart);
				$r .= number_format($executionSeconds * 1000, 0)."ms. ";
				$r .= "Logging: ";

				$databaseProviderName = $this->getConfig("logDatabaseProviderName");
				$result = $e->Database->$databaseProviderName->prepareAndExecute(
					"insert into ".$this->getConfig("logTableName")." (executionDate, executionSeconds, taskName, resultCode, resultDescription) values (?, ?, ?, ?, ?)",
					[
						[
							"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME,
							"value" => $baseTimestamp
						],
						[
							"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_FLOAT,
							"value" => $executionSeconds
						],
						[
							"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
							"value" => $janitorTask->getName()
						],
						[
							"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
							"value" => $resultCode
						],
						[
							"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
							"value" => json_encode($resultDescription)
						]
					]
				);

				if (!$result)
					$r .= "Failed. ";
				else
					$r .= "Ok. ";

				$r .= "Result: ";
				$r .= $this->getJanitorTaskReturnCodeDescription($resultCode).". ";


				if ($resultCode != \Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_OK) {
					$r .= "Logging error: ";
					$e->Errors->trigger(
						\Cherrycake\Modules\ERROR_SYSTEM,
						[
							"errorDescription" => "JanitorTask failed",
							"errorVariables" => [
								"JanitorTask name" => $janitorTask->getName(),
								"JanitorTask result code" => $this->getJanitorTaskReturnCodeDescription($resultCode),
								"JanitorTask result description" => $resultDescription
							],
							"isSilent" => true
						]
					);
					$r .= "Ok. ";
				}

				if ($resultDescription) {
					$r .= "\n";
					if (!is_array($resultDescription))
						$r .= "   ".$resultDescription."\n";
					else
						foreach ($resultDescription as $key => $value)
							$r .= "   ".$key.": ".$value."\n";
				}
			}
			else
				$r .= "Not to be executed\n";
		}
		reset($this->janitorTasks);

		$e->Output->setResponse(new \Cherrycake\ResponseTextPlain([
			"payload" => $r
		]));
	}

	/**
	 * status
	 *
	 * Presents a page with Janitor's current status
	 */
	function status($request) {
		if (!$this->checkKey($request->key))
			return;
		echo $this->getStatusHtml();
	}

	/**
	 * getStatus
	 *
	 * Returns an array containing status info for all Janitor tasks
	 *
	 * @return array An (n-dimensional hash array containing status info for janitor tasks
	 */
	function getStatus() {
		while (list($janitorTaskName, $janitorTask) = each($this->janitorTasks)) {
			$r[$janitorTaskName] = $janitorTask->getStatus();
		}
		reset($this->janitorTasks);
		return $r;
	}

	/**
	 * Returns the description of the given JanitorTask return code
	 *
	 * @param integer $returnCode The return code to get the description of
	 */
	static function getJanitorTaskReturnCodeDescription($returnCode) {
		switch ($returnCode) {
			case JANITORTASK_EXECUTION_RETURN_WARNING:
				return "Warning";
				break;
			case JANITORTASK_EXECUTION_RETURN_ERROR:
				return "Error";
				break;
			case JANITORTASK_EXECUTION_RETURN_CRITICAL:
				return "Critical";
				break;
			case JANITORTASK_EXECUTION_RETURN_OK:
				return "Ok";
				break;
			default:
				return "Unknown ".$returnCode." return code";
				break;
		}
	}

	/**
	 * getStatusHtml
	 *
	 * Returns the status for all Janitor tasks in a formated HTML string
	 *
	 * @setup array $setup Setup options, available keys:
	 *  - tableClass: The CSS class to use for the table
	 * @return string The HTML
	 */
	function getStatusHtml($setup = false) {
		global $e;

		$this->loadTasks();

		while (list($janitorTaskName, $janitorTask) = each($this->janitorTasks)) {
			$taskStatus = $janitorTask->getStatus();

			$r .=
				"<table class=\"".($setup["tableClass"] ? $setup["tableClass"] : "debugInfo")."\">".
				"<tr>".
					"<th colspan=2>".
						"<h2>".$janitorTask->getName()."</h2>".
						"<h3>".$janitorTask->getDescription()."</h3>".
						$this->getJanitorTaskReturnCodeDescription($taskStatus["lastExecutionResultCode"]).
					"</th>".
				"</tr>".
				"<tr>".
					"<td colspan=2>".$janitorTask->getPeriodicityDebugInfo()."</td>".
				"</tr>";

			if (!$taskStatus)
				$r .=
					"<tr>".
						"<td colspan=2>Never executed</td>".
					"</tr>";
			else {
				$r .=
					"<tr>".
						"<td>Last execution</td>".
						"<td>".date("j/n/Y H:i:s", $taskStatus["lastExecutionTimestamp"])." (".date_default_timezone_get().") took ".number_format($taskStatus["lastExecutionSeconds"]*1000)." ms.</td>".
					"</tr>".
					"<tr>".
						"<td></td>".
						"<td>";

							if (is_array($taskStatus["lastExecutionResultDescription"])) {
								while (list($key, $value) = each($taskStatus["lastExecutionResultDescription"]))
									$r .= "<b>".$key.":</b> ".$value."<br>";
							}
							else
								$r .= "No report";

				$r .=
						"</td>".
					"</tr>".
					"</table>";
			}
		}

		return $r;
	}

	/**
	 * Get the log in HTML format
	 * This method expects that the engine has been loaded with the following modules:
	 * * Database
	 * * Ui
	 * * UiComponentTable 
	 * 
	 * @param array $setup Setup parameters
	 * @return string The HTML
	 */
	function getLogHtml($setup = false) {
		global $e;

		$janitorLogItems = new \Cherrycake\JanitorLogItems([
			"fillMethod" => "fromParameters",
			"p" => [
				"limit" => 100
			]
		]);

		return \Cherrycake\UiComponentTable::build([
			"items" => $janitorLogItems,
			"itemFields" => [
				"id" => [],
				"executionDate" => [],
				"executionSeconds" => [],
				"taskName" => [],
				"resultCode" => [],
				"resultDescription" => []
			],
			"additionalCssClasses" => "fullWidth"
		])->buildHtml();
	}

	/**
	 * Gets debug information in HTML format
	 * 
	 * @setup array $setup Setup options, available keys:
	 *  - tableClass: The CSS class to use for the table
	 * @return string Debug info for all configured tasks in HTML format
	 */
	function getDebugInfoHtml($setup = false) {
		$this->loadTasks();

		while (list($janitorTaskName, $janitorTask) = each($this->janitorTasks))
			$r .= $janitorTask->getDebugInfoHtml();
				
		reset($this->janitorTasks);
		return $r;
	}
}