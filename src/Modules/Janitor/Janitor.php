<?php

namespace Cherrycake\Modules\Janitor;

use Cherrycake\Classes\Engine;

/**
 * Allows an app to program tasks to be executed automatically and periodically.
 *
 * It adds two actions:
 *  /janitor/run
 *      Runs all the tasks that must run at the time of request.
 *      Can receive the "task" GET parameter with the name of a task to be individually executed. It considers all configured tasks if not specified.
 *
 *  /janitor/status
 *      Presents a page with a tasks report status
 */
class Janitor extends \Cherrycake\Classes\Module {

	const EXECUTION_RETURN_WARNING = 0; // Return code for JanitorTask run when task returned a warning.
	const EXECUTION_RETURN_ERROR = 1; // Return code for JanitorTask run when task returned an error.
	const EXECUTION_RETURN_CRITICAL = 2; // Return code for JanitorTask run when task returned a critical error.
	const EXECUTION_RETURN_OK = 3; // Return code for JanitorTask run when task was executed without errors.

	const EXECUTION_PERIODICITY_ONLY_MANUAL = 0; // The task can only be executed when calling the Janitor run process with an specific task parameter.
	const EXECUTION_PERIODICITY_ALWAYS = 1; // The task will be executed every time Janitor run is called.
	const EXECUTION_PERIODICITY_EACH_SECONDS = 2; // The task will be executed every specified seconds. Seconds are specified in "periodicityEachSeconds" config key.
	const EXECUTION_PERIODICITY_MINUTES = 3; // The task will be executed on the given minutes of each hour. Desired minutes are specified as an array in the "periodicityMinutes" config key. For example: [0, 15, 30, 45]
	const EXECUTION_PERIODICITY_HOURS = 4; // The task will be executed on the given hours of each day. Desired hours/minute are specified as an array in the "periodicityHours" config key in the syntax ["hour:minute", ...] For example: ["00:00", "10:45", "20:15"]
	const EXECUTION_PERIODICITY_DAYSOFMONTH = 5; // The task will be executed on the given days of each month. Desired days/hour/minute are specified as an array in the "periodicityDaysOfMonth" config key in the syntax ["day@hour:minute", ...] For example: ["1@12:00", "15@18:30", "20@00:00"] (Take into account days of month that do not exist)

	/**
	 * @var array $config Holds the default configuration for this module
	 */
	protected array $config = [
		"logDatabaseProviderName" => "main", // The name of the DatabaseProvider to use for storing Janitor log.
		"logTableName" => "cherrycake_janitor_log", // The name of the table used to store Janitor log.
		"coreJanitorTasks" => [ // An array of names of Cherrycake core JanitorTask classes to run.
			"JanitorPurge",
			"LogCommit",
			"SessionPurge",
			"StatsCommit",
			"SystemLogCommit",
			"SystemLogPurge"
		],
		"appJanitorTasks" => [] // An array of names of App JanitorTask classes to run.
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Errors",
		"Database"
	];

	/**
	 * var @array $janitorTasks The array of JanitorTask objects that have been added to Janitor
	 */
	var $janitorTasks;

	/**
	 * mapActions
	 *
	 * Maps the Actions to which this module must respond
	 */
	public static function mapActions() {
		Engine::e()->Actions->mapAction(
			"janitorRun",
			new \Cherrycake\Modules\Actions\ActionCli(
				moduleType: \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_CORE,
				moduleName: "Janitor",
				methodName: "run",
				request: new \Cherrycake\Modules\Actions\Request(
					pathComponents: [
						new \Cherrycake\Modules\Actions\RequestPathComponent(
							type: \Cherrycake\Modules\Actions\Request::PATH_COMPONENT_TYPE_FIXED,
							string: "janitor"
						),
						new \Cherrycake\Modules\Actions\RequestPathComponent(
							type: \Cherrycake\Modules\Actions\Request::PATH_COMPONENT_TYPE_FIXED,
							string: "run"
						)
					],
					parameters: [
						new \Cherrycake\Modules\Actions\RequestParameter(
							name: "task",
							type: \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_GET
						),
						new \Cherrycake\Modules\Actions\RequestParameter(
							name: "isForceRun",
							type: \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_GET
						)
					]
				)
			)
		);

		Engine::e()->Actions->mapAction(
			"janitorStatus",
			new \Cherrycake\Modules\Actions\ActionCli(
				moduleType: \Cherrycake\Modules\Actions\Actions::MODULE_TYPE_CORE,
				moduleName: "Janitor",
				methodName: "showPlainStatus",
				request: new \Cherrycake\Modules\Actions\Request(
					pathComponents: [
						new \Cherrycake\Modules\Actions\RequestPathComponent(
							type: \Cherrycake\Modules\Actions\Request::PATH_COMPONENT_TYPE_FIXED,
							string: "janitor"
						),
						new \Cherrycake\Modules\Actions\RequestPathComponent(
							type: \Cherrycake\Modules\Actions\Request::PATH_COMPONENT_TYPE_FIXED,
							string: "status"
						)
					]
				)
			)
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

		// Sets up Janitor tasks
		if (is_array($coreJanitorTasks = $this->getConfig("coreJanitorTasks")))
			foreach($coreJanitorTasks as $cherrycakeJanitorTask)
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
		if (!isset($this->janitorTasks[$janitorTaskName])) {
			eval("\$this->janitorTasks[\"".$janitorTaskName."\"] = new \\Cherrycake\\Classes\\JanitorTask\\".$janitorTaskName."();");
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
		if (!isset($this->janitorTasks[$janitorTaskName])) {
			eval("\$this->janitorTasks[\"".$janitorTaskName."\"] = new \\".Engine::e()->getAppNamespace()."\\Classes\\Janitor\\".$janitorTaskName."();");
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
	 * run
	 *
	 * Determines which tasks need to be executed now and executes them. If a task name is passed via Request in the task parameter, only that task will be executed, if due to be executed. If, additionally, the isForceRun parameter is passed as true, the task name will be executed even if it's not due to be executed.
	 * @param Request $request A Request object, passed by the Actions module when calling this method as the result of an action.
	 */
	function run($request) {
		$task = $request->task;
		$isForceRun = $request->isForceRun;

		$this->loadTasks();

		$r = "Janitor run\n";

		$baseTimestamp = time();

		$r .= "Task to be considered: ".($task ? $task : "All")."\n";

		$r .= "Base timestamp: ".date("j/n/Y H:i:s", $baseTimestamp)."\n";

		if ($task && !$this->isTask($task)) {
			$r .= "Specified task does not exists";
			return false;
		}

		if (is_array($this->janitorTasks)) {
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
					$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
						"insert into ".$this->getConfig("logTableName")." (executionDate, executionSeconds, taskName, resultCode, resultDescription) values (?, ?, ?, ?, ?)",
						[
							[
								"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
								"value" => $baseTimestamp
							],
							[
								"type" => \Cherrycake\Modules\Database\Database::TYPE_FLOAT,
								"value" => $executionSeconds
							],
							[
								"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
								"value" => $janitorTask->getName()
							],
							[
								"type" => \Cherrycake\Modules\Database\Database::TYPE_INTEGER,
								"value" => $resultCode
							],
							[
								"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
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


					if ($resultCode != \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_OK) {
						$r .= "Logging error: ";
						Engine::e()->Errors->trigger(
							type: Errors::ERROR_SYSTEM,
							description: "JanitorTask failed",
							variables: [
								"JanitorTask name" => $janitorTask->getName(),
								"JanitorTask result code" => $this->getJanitorTaskReturnCodeDescription($resultCode),
								"JanitorTask result description" => $resultDescription
							],
							isSilent: true
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
		}

		Engine::e()->Output->setResponse(new \Cherrycake\Modules\Actions\ResponseTextPlain(payload: $r));
	}

	/**
	 * Shows the status of the Janitor tasks in plain text
	 */
	function showPlainStatus($request) {
		$this->loadTasks();

		$r = "";
		foreach ($this->janitorTasks as $janitorTaskName => $janitorTask) {
			$taskStatus = $janitorTask->getStatus();

			$r .=
				"Task: ".$janitorTask->getName()."\n".
				"Description: ".$janitorTask->getDescription()."\n".
				"Result: ".$this->getJanitorTaskReturnCodeDescription($taskStatus["lastExecutionResultCode"] ?? false)."\n".
				"Periodicity: ".$janitorTask->getPeriodicityDebugInfo()."\n";

			if (!$taskStatus)
				$r .= "Never executed\n";
			else {
				$r .=
					"Last execution: ".
					date("j/n/Y H:i:s", $taskStatus["lastExecutionTimestamp"])." (".date_default_timezone_get().") took ".number_format($taskStatus["lastExecutionSeconds"]*1000)." ms.\n";

					if (isset($taskStatus["lastExecutionResultDescription"])) {
						if (is_array($taskStatus["lastExecutionResultDescription"])) {
							foreach ($taskStatus["lastExecutionResultDescription"] as $key => $value)
								$r .= ". ".$key.": ".$value."\n";
						}
						else
							$r .= ". ".$taskStatus["lastExecutionResultDescription"];
					}
					else
						$r .= "No report\n";
				$r .= "\n";
			}
		}

		Engine::e()->Output->setResponse(new \Cherrycake\Modules\Actions\ResponseTextPlain(payload: $r));
	}

	/**
	 * getStatus
	 *
	 * Returns an array containing status info for all Janitor tasks
	 *
	 * @return array An (n-dimensional hash array containing status info for janitor tasks
	 */
	function getStatus() {
		$this->loadTasks();
		foreach ($this->janitorTasks as $janitorTaskName => $janitorTask) {
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
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_WARNING:
				return "Warning";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_ERROR:
				return "Error";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_CRITICAL:
				return "Critical";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_OK:
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

		$this->loadTasks();

		$r = "";
		foreach ($this->janitorTasks as $janitorTaskName => $janitorTask) {
			$taskStatus = $janitorTask->getStatus();

			$r .=
				"<table class=\"".($setup["tableClass"] ?? false ? $setup["tableClass"] : "debugInfo")."\">".
				"<tr>".
					"<th colspan=2>".
						"<h2>".$janitorTask->getName()."</h2>".
						"<h3>".$janitorTask->getDescription()."</h3>".
						$this->getJanitorTaskReturnCodeDescription($taskStatus["lastExecutionResultCode"] ?? false).
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
								foreach ($taskStatus["lastExecutionResultDescription"] as $key => $value)
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
	 * Gets debug information in HTML format
	 *
	 * @setup array $setup Setup options, available keys:
	 *  - tableClass: The CSS class to use for the table
	 * @return string Debug info for all configured tasks in HTML format
	 */
	function getDebugInfoHtml($setup = false) {
		$this->loadTasks();

		$r = "";
		foreach ($this->janitorTasks as $janitorTaskName => $janitorTask)
			$r .= $janitorTask->getDebugInfoHtml();

		reset($this->janitorTasks);
		return $r;
	}
}
