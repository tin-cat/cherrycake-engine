<?php

namespace Cherrycake\Classes\JanitorTask;

use Cherrycake\Classes\Engine;

/**
 * A JanitorTask to maintain the Janitor module itself
 * Purges the old log items on the database to avoid unnecessary database cluttering.
 */
class JanitorPurge extends \Cherrycake\Modules\Janitor\JanitorTask {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"executionPeriodicity" => \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL by default.
		"periodicityEachSeconds" => 86400, // (86400 = 1 day)
		"purgeLogsOlderThanSeconds" => 2592000 // Log entries older than this seconds will be purged. // 2592000 = 30 days, 31536000 = 365 days
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name = "Janitor purge";

	/**
	 * @var string $description The description of the task
	 */
	protected $description = "Purges old Janitor log items";

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
		Engine::e()->loadCoreModule("Janitor");

		$databaseProviderName = Engine::e()->Janitor->getConfig("logDatabaseProviderName");

		// Purge sessions older than PurgeSessionsWithoutDataOlderThanSeconds without data
		$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
			"select count(*) as numberOf from ".Engine::e()->Janitor->getConfig("logTableName")." where executionDate < ?",
			[
				[
					"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
					"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds"))
				]
			]
		);

		if (!$result)
			return [
				\Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_ERROR,
				"Could not query the database"
			];

		$row = $result->getRow();
		$numberOfLogEntriesToPurge = $row->getField("numberOf");

		if ($numberOfLogEntriesToPurge > 0) {
			$result = Engine::e()->Database->$databaseProviderName->prepareAndExecute(
				"delete from ".Engine::e()->Janitor->getConfig("logTableName")." where executionDate < ?",
				[
					[
						"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
						"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeLogsOlderThanSeconds"))
					]
				]
			);

			if (!$result)
				return [
					\Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_ERROR,
					"Could not delete log entries from the database"
				];
		}

		return [
			\Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_OK,
			[
				"Log entries older than ".$this->getConfig("purgeLogsOlderThanSeconds")." seconds purged" => $numberOfLogEntriesToPurge
			]
		];
	}
}
