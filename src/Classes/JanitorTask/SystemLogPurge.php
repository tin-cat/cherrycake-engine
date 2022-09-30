<?php

namespace Cherrycake\Classes\JanitorTask;

use Cherrycake\Classes\Engine;

/**
 * A JanitorTask to purge the SystemLog module database
 * Purges the old log items on the database to avoid unnecessary database cluttering.
 */
class SystemLogPurge extends \Cherrycake\Modules\Janitor\JanitorTask {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"executionPeriodicity" => \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL by default.
		"periodicityEachSeconds" => 240,
		"purgeLogsOlderThanSeconds" => 2592000 // Log entries older than this seconds will be purged. // 2592000 = 30 days, 31536000 = 365 days
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name = "System log purge";

	/**
	 * @var string $description The description of the task
	 */
	protected $description = "Purges old System log items";

	/**
	 * getDebugInfo
	 *
	 * Returns a hash array with debug info for this task. Can be overloaded to return additional info, on which case the specific results should be merged with this results with array_merge(parent::getDebugInfo(), <specific debug info array>)
	 *
	 * @return array Hash array with debug info for this task
	 */
	function getDebugInfo() {
		return array_merge(parent::getDebugInfo(), [
			"purgeLogsOlderThanSeconds" => $this->getConfig("purgeLogsOlderThanSeconds")
		]);
	}

	/**
	 * run
	 *
	 * Performs the tasks for what this JanitorTask is meant.
	 *
	 * @param integer $baseTimestamp The base timestamp to use for time-based calculations when running this task. Usually, now.
	 * @return array A one-dimensional array with the keys: {<One of \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_? consts>, <Task result/error/health check description. Can be an array if different keys of information need to be given.>}
	 */
	function run($baseTimestamp) {

		// Loads the needed modules
		Engine::e()->loadCoreModule("SystemLog");

		$baseTimestamp = time();

		$result = Engine::e()->Database->{Engine::e()->SystemLog->getConfig("databaseProviderName")}->prepareAndExecute(
			"select count(*) as numberOf from ".Engine::e()->SystemLog->getConfig("tableName")." where dateAdded < ?",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
					"value" => $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds")
				]
			]
		);

		if (!$result)
			return [
				false,
				"Could not query the database"
			];

		$row = $result->getRow();
		$numberOfLogEntriesToPurge = $row->getField("numberOf");

		if ($numberOfLogEntriesToPurge > 0) {
			$result = Engine::e()->Database->{Engine::e()->SystemLog->getConfig("databaseProviderName")}->prepareAndExecute(
				"delete from ".Engine::e()->SystemLog->getConfig("tableName")." where dateAdded < ?",
				[
					[
						"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
						"value" => $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds")
					]
				]
			);

			if (!$result)
				return [
					false,
					"Could not delete log entries from the database"
				];
		}

		return [
			true,
			[
				"Log entries older than ".$this->getConfig("purgeLogsOlderThanSeconds")." seconds purged" => $numberOfLogEntriesToPurge
			]
		];
	}

	/**
	 * Purges logs older than purgeLogsOlderThanSeconds
	 * @return array An array where the first element is a boolean indicating wether the operation went ok or not, and the second element is a description of what happened.
	 */
	function purge() {


	}
}
