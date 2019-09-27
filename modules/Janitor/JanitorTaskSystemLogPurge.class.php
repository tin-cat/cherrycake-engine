<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A JanitorTask to purge the SystemLog module database
 * Purges the old log items on the database to avoid unnecessary database cluttering.
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorTaskSystemLogPurge extends JanitorTask
{
	/**
	 * @var bool $isConfig Sets whether this JanitorTask has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	protected $config = [
		"executionPeriodicity" => \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\Modules\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL by default.
		"periodicityEachSeconds" => 240,
		"purgeLogsOlderThanSeconds" => 31536000 // Log entries older than this seconds will be purged. (31536000 = 365 days)
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
	 * @return array A one-dimensional array with the keys: {<One of JANITORTASK_EXECUTION_RETURN_? consts>, <Task result/error/health check description. Can be an array if different keys of information need to be given.>}
	 */
	function run($baseTimestamp) {
		global $e;

		// Loads the needed modules
		$e->loadCherrycakeModule("SystemLog");

		list($result, $resultDescription) = $e->SystemLog->purge();
		return [
			$result ? \Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_OK : \Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_ERROR,
			$resultDescription
		];
	}
}