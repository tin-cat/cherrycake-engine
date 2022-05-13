<?php

namespace Cherrycake\Modules\Janitor;

use Cherrycake\Engine;

/**
 * Base class for Janitor tasks.
 */
class JanitorTask {
	/**
	 * @var array $config Holds the default configuration for this JanitorTask
	 */
	protected array $config = [
		"executionPeriodicity" => \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL, // The periodicity for this task execution. One of the available \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_? constants.
		"periodicityEachSeconds" => false, // When executionPeriodicity is set to \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS, this is the number of seconds between each execution.
		"periodicityMinutes" => false, // When executionPeriodicity is set to \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_MINUTES, this is an array specifying the minutes within each hour when this task will be executed, in the syntax: [0, 15, 30, 45, ...]
		"periodicityHours" => false, // When executionPeriodicity is set to \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_HOURS, this is an array specifying the hours and minutes within each day when this task will be executed, in the syntax: ["hour:minute", "hour:minute", "hour:minute", ...]
		"periodicityDaysOfMonth" => false, // When executionPeriodicity is set to \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_DAYSOFMONTH, this is an array specifying the days, hours and minutes within each month this task will be executed, in the syntax: ["day@hour:minute", "day@hour:minute", "day@hour:minute", ...]
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name;

	/**
	 * @var string $description The description of the task
	 */
	protected $description;

	/**
	 * loadConfigFile
	 *
	 * Loads the configuration file for this JanitorTask, if there's one
	 */
	function loadConfigFile() {
		$className = substr(get_class($this), strpos(get_class($this), "\\")+1);
		$fileName = Engine::e()->getConfigDir()."/".$className.".config.php";
		if (!file_exists($fileName))
			return;
		include $fileName;
		if (isset(${$className."Config"}))
			$this->config(${$className."Config"});
	}

	/**
	 * config
	 *
	 * Sets the configuration
	 *
	 * @param array $config An array of configuration options for this janitor task. It merges them with the hard coded default values configured in the overloaded janitor task class.
	 */
	function config($config) {
		if (!$config)
			return;

		if (is_array($this->config))
			$this->config = array_merge($this->config, $config);
		else
			$this->config = $config;
	}

	/**
	 * init
	 *
	 * Initializes the JanitorTask, intended to be overloaded.
	 * Called when the JanitorTask is loaded.
	 * Contains any specific initializations for the JanitorTask, and any required loading of dependencies.
	 *
	 * @return boolean Whether the JanitorTask has been loaded ok
	 */
	function init(): bool {
		$this->loadConfigFile();
		return true;
	}

	/**
	 * getConfig
	 *
	 * Gets a configuration value
	 *
	 * @param string $key The configuration key
	 */
	function getConfig($key) {
		return $this->config[$key];
	}

	/**
	 * run
	 *
	 * Performs the tasks for what this JanitorTask is meant. Must be overloaded by a higher level JanitorTask class.
	 *
	 * @param integer $baseTimestamp The base timestamp to use for time-based calculations when running this task. Usually, now.
	 * @return array An array with the following values:
	 * * One of the \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_? available constants indicating the result of the task execution.
	 * * The description of the task result, or a hash array of information on the result of the task execution.
	 */
	function run($baseTimestamp) {
	}

	/**
	 * getName
	 *
	 * @return string The name of the task
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * getDescription
	 *
	 * @return string The description of the task
	 */
	function getDescription() {
		return $this->description;
	}

	/**
	 * getLastExecutionTimestamp
	 *
	 * @return mixed The timestamp on which this task ran for the last time. False if haven't ever run.
	 */
	function getLastExecutionTimestamp() {

		$databaseProviderName = Engine::e()->Janitor->getConfig("logDatabaseProviderName");
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"select executionDate from ".Engine::e()->Janitor->getConfig("logTableName")." where taskName = ? order by executionDate desc limit 1",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $this->getName()
				]
			],
			[
				"timestampFieldNames" => ["executionDate"]
			]
		);

		if (!$result->isAny())
			return false;

		$row = $result->getRow();
		return $row->getField("executionDate");
	}

	/**
	 * isToBeExecuted
	 *
	 * Determines whether this task should be executed on the given timestamp (usually now)
	 *
	 * @param integer $baseTimestamp The timestamp to use for the calculation, usually now. If not provided, the present time is used.
	 * @return bool Whether this task should be executed on the given timestamp (usually now)
	 */
	function isToBeExecuted($baseTimestamp = false) {
		$executionPeriodicity = $this->getConfig("executionPeriodicity");

		if ($executionPeriodicity == \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ALWAYS || $executionPeriodicity == \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL)
			return true;

		if (!$baseTimestamp)
			$baseTimestamp = time();

		if (!$lastExecutionTimestamp = $this->getLastExecutionTimestamp())
			return true;

		switch ($executionPeriodicity) {
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS:
				$periodicityEachSeconds = $this->getConfig("periodicityEachSeconds");
				return ($lastExecutionTimestamp + $periodicityEachSeconds) < $baseTimestamp;
				break;

			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_MINUTES:
				$minutes = $this->getConfig("periodicityMinutes");
				if (!is_array($minutes))
					$minutes = [$this->getConfig("periodicityMinutes")];
				foreach ($minutes as $minute) {
					$nextExecution = mktime(
						date("H", $lastExecutionTimestamp),
						$minute,
						0,
						date("n", $lastExecutionTimestamp),
						date("j", $lastExecutionTimestamp),
						date("Y", $lastExecutionTimestamp)
					);
					if ($nextExecution < $lastExecutionTimestamp)
						$nextExecution = mktime(
							date("H", $lastExecutionTimestamp) + 1,
							$minute,
							0,
							date("n", $lastExecutionTimestamp),
							date("j", $lastExecutionTimestamp),
							date("Y", $lastExecutionTimestamp)
						);
					if ($baseTimestamp > $nextExecution)
						return true;
				}
				return false;
				break;

			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_HOURS:
				$hourTokens = $this->getConfig("periodicityHours");
				if (!is_array($hourTokens))
					$hourTokens = [$this->getConfig("periodicityHours")];
				foreach ($hourTokens as $hourToken) {
					list($hour, $minute) = explode(":", $hourToken);
					$nextExecution = mktime(
						$hour,
						$minute,
						0,
						date("n", $lastExecutionTimestamp),
						date("j", $lastExecutionTimestamp),
						date("Y", $lastExecutionTimestamp)
					);
					if ($nextExecution < $lastExecutionTimestamp)
						$nextExecution = mktime(
							$hour,
							$minute,
							0,
							date("n", $lastExecutionTimestamp),
							date("j", $lastExecutionTimestamp) + 1,
							date("Y", $lastExecutionTimestamp)
						);
					if ($baseTimestamp > $nextExecution)
						return true;
				}
				return false;
				break;

			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_DAYSOFMONTH:
				$dayTokens = $this->getConfig("periodicityDaysOfMonth");
				if (!is_array($dayTokens))
					$dayTokens = [$this->getConfig("periodicityDaysOfMonth")];
				foreach ($dayTokens as $dayToken) {
					list($day, $hourToken) = explode("@", $dayToken);
					list($hour, $minute) = explode(":", $hourToken);
					$nextExecution = mktime(
						$hour,
						$minute,
						0,
						date("n", $lastExecutionTimestamp),
						$day,
						date("Y", $lastExecutionTimestamp)
					);
					if ($nextExecution < $lastExecutionTimestamp)
						$nextExecution = mktime(
							$hour,
							$minute,
							0,
							date("n", $lastExecutionTimestamp) + 1,
							$day,
							date("Y", $lastExecutionTimestamp)
						);
					if ($baseTimestamp > $nextExecution)
						return true;
				}
				return false;
				break;
		}
	}

	/**
	 * getStatus
	 *
	 * Returns a hash array containing status information about this task: The last execution and status.
	 *
	 * @return mixed A hash array containing status information about this task. Return false if no info about the last execution of this task could be retrieved.
	 */
	function getStatus() {

		// Get last execution log for this task
		$databaseProviderName = Engine::e()->Janitor->getConfig("logDatabaseProviderName");
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"select executionDate, executionSeconds, resultCode, resultDescription from ".Engine::e()->Janitor->getConfig("logTableName")." where taskName = ? order by executionDate desc limit 1",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => $this->getName()
				]
			],
			[
				"timestampFieldNames" => ["executionDate"]
			]
		);

		if (!$result->isAny())
			return false;

		$row = $result->getRow();

		return [
			"lastExecutionTimestamp" => $row->getField("executionDate"),
			"lastExecutionSeconds" => $row->getField("executionSeconds"),
			"lastExecutionResultCode" => $row->getField("resultCode"),
			"lastExecutionResultDescription" => ($row->getField("resultDescription") ? json_decode($row->getField("resultDescription"), true) : false)
		];
	}

	/**
	 * getDebugInfo
	 *
	 * Returns a hash array with debug info for this task. Can be overloaded to return additional info, on which case the specific results should be merged with this results with array_merge(parent::getDebugInfo(), <specific debug info array>)
	 *
	 * @return array Hash array with debug info for this task
	 */
	function getDebugInfo() {
		return array_merge([
			"Name" => $this->getName(),
			"Description" => $this->getDescription()
		], $this->getPeriodicityDebugInfo());
	}

	/**
	 * getPeriodicityDebugInfo
	 *
	 * @return array Hash array with debug info about the periodicity of this task.
	 */
	function getPeriodicityDebugInfo() {
		switch ($this->getConfig("executionPeriodicity")) {
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL:
				$description = "Manual";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ALWAYS:
				$description = "Every time";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS:
				$description = "Every ".$this->getConfig("periodicityEachSeconds")." seconds";
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_MINUTES:
				$description = "Hourly on ".(!is_array($this->getConfig("periodicityMinutes")) ? "minute ".$this->getConfig("periodicityMinutes") : "minutes ".implode(", ", $this->getConfig("periodicityMinutes")));
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_HOURS:
				$description = "Daily at ".(!is_array($this->getConfig("periodicityHours")) ? "hour ".$this->getConfig("periodicityHours") : "hours ".implode(", ", $this->getConfig("periodicityHours")));
				break;
			case \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_DAYSOFMONTH:
				$description = "Monthly on ".(!is_array($this->getConfig("periodicityDaysOfMonth")) ? "day ".$this->getConfig("periodicityDaysOfMonth") : "days ".implode(", ", $this->getConfig("periodicityDaysOfMonth")));
				break;
		}
		return $description;
	}

	/**
	 * getDebugInfoHtml
	 *
	 * Returns debug info about this task in HTML format
	 *
	 * @setup array $setup Setup options, available keys:
	 *  - tableClass: The CSS class to use for the table
	 * @return string Debug info about this task in HTML format
	 */
	function getDebugInfoHtml($setup = false) {
		$debugInfo = $this->getDebugInfo();

		$r .= "<table class=\"".($setup["tableClass"] ? $setup["tableClass"] : "debugInfo")."\"><tr><th colspan=2><h2>".$this->getName()."</h2><h3>".$this->getDescription()."</h3></th></tr>";
		foreach ($debugInfo as $key => $value)
			if ($key != "Name" && $key != "Description")
				$r .= "<tr class=\"keyValue\"><td>".$key."</td><td>".$value."</td></tr>";
		$r .= "</table>";

		return $r;
	}
}
