<?php

/**
 * JanitorTask
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * JanitorTask
 *
 * Base class for Janitor tasks.
 *
 * Configuration example for <taskname>.config.php:
 * <code>
 * $<taskname>Config = [
 *  "executionPeriodicity" => \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL by default.
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorTask
{
	/**
	 * @var bool $isConfig Sets whether this JanitorTask has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * @var array $config Holds the default configuration for this JanitorTask
	 */
	protected $config = [
		"executionPeriodicity" => \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL
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
		if ($this->isConfigFile) {
			global $e;
			$className = substr(get_class($this), strpos(get_class($this), "\\")+1);
			include $e->getConfigDir()."/Janitor/".$className.".config.php";
			if (isset(${$className."Config"}))
				$this->config(${$className."Config"});
		}
	}

	/**
	 * config
	 *
	 * Sets the ui component configuration
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
	function init() {
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
	function getConfig($key)
	{
		return $this->config[$key];
	}

	/**
	 * run
	 *
	 * Performs the tasks for what this JanitorTask is meant. Must be overloaded by a higher level JanitorTask class.
	 *
	 * @param integer $baseTimestamp The base timestamp to use for time-based calculations when running this task. Usually, now.
	 * @return array A one-dimensional array with the keys: {<One of JANITORTASK_EXECUTION_RETURN_? consts>, <Task result/error/health check description. Can be an array if different keys of information need to be given.>}
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
		global $e;

		$databaseProviderName = $e->Janitor->getConfig("logDatabaseProviderName");
		$result = $e->Database->$databaseProviderName->prepareAndExecute(
			"select executionDate from ".$e->Janitor->getConfig("logTableName")." where taskName = ? order by executionDate desc limit 1",
			[
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
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

		if ($executionPeriodicity == \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ALWAYS || $executionPeriodicity == \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL)
			return true;

		if (!$baseTimestamp)
			$baseTimestamp = time();

		if (!$lastExecutionTimestamp = $this->getLastExecutionTimestamp())
			return true;

		switch ($executionPeriodicity) {
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS:
				$periodicityEachSeconds = $this->getConfig("periodicityEachSeconds");
				return ($lastExecutionTimestamp + $periodicityEachSeconds) < $baseTimestamp;
				break;

			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_MINUTES:
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

			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_HOURS:
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

			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_DAYSOFMONTH:
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
		global $e;

		// Get last execution log for this task
		$databaseProviderName = $e->Janitor->getConfig("logDatabaseProviderName");
		$result = $e->Database->$databaseProviderName->prepareAndExecute(
			"select executionDate, executionSeconds, resultCode, resultDescription from ".$e->Janitor->getConfig("logTableName")." where taskName = ? order by executionDate desc limit 1",
			[
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
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
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL:
				$description = "Manual";
				break;
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ALWAYS:
				$description = "Every time";
				break;
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS:
				$description = "Every ".$this->getConfig("periodicityEachSeconds")." seconds";
				break;
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_MINUTES:
				$description = "Hourly on ".(!is_array($this->getConfig("periodicityMinutes")) ? "minute ".$this->getConfig("periodicityMinutes") : "minutes ".implode(", ", $this->getConfig("periodicityMinutes")));
				break;
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_HOURS:
				$description = "Daily at ".(!is_array($this->getConfig("periodicityHours")) ? "hour ".$this->getConfig("periodicityHours") : "hours ".implode(", ", $this->getConfig("periodicityHours")));
				break;
			case \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_DAYSOFMONTH:
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
		while (list($key, $value) = each($debugInfo))
			if ($key != "Name" && $key != "Description")
				$r .= "<tr class=\"keyValue\"><td>".$key."</td><td>".$value."</td></tr>";
		$r .= "</table>";

		return $r;
	}
}