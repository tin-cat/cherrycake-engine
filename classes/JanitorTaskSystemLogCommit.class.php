<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A JanitorTask to maintain the System Log module
 * Commits the system log events in cache to database
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorTaskSystemLogCommit extends JanitorTask {
	/**
	 * @var array $config Default configuration options
	 */
	protected $config = [
		"executionPeriodicity" => \Cherrycake\JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL by default.
		"periodicityEachSeconds" => 120
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name = "System log commit";

	/**
	 * @var string $description The description of the task
	 */
	protected $description = "Stores cache-queded system log events into database and purges the queue cache";

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
		$e->loadCoreModule("SystemLog");

		list($result, $resultDescription) = $e->SystemLog->commit();
		return [
			$result ? \Cherrycake\JANITORTASK_EXECUTION_RETURN_OK : \Cherrycake\JANITORTASK_EXECUTION_RETURN_ERROR,
			$resultDescription
		];
	}
}