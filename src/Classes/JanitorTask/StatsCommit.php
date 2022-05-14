<?php

namespace Cherrycake\Classes\JanitorTask;

use Cherrycake\Classes\Engine;

/**
 * A JanitorTask to maintain the Stats module
 * Commits the Stats events in cache to database
 */
class StatsCommit extends \Cherrycake\Modules\Janitor\JanitorTask {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		"executionPeriodicity" => \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_EACH_SECONDS, // The periodicity for this task execution. One of the available CONSTs. \Cherrycake\Modules\Janitor\Janitor::EXECUTION_PERIODICITY_ONLY_MANUAL by default.
		"periodicityEachSeconds" => 60
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name = "Stats commit";

	/**
	 * @var string $description The description of the task
	 */
	protected $description = "Stores cache-queded stats events into database and purges the queue cache";

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
		Engine::e()->loadCoreModule("Stats");

		list($result, $resultDescription) = Engine::e()->Stats->commit();
		return [
			$result ? \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_OK : \Cherrycake\Modules\Janitor\Janitor::EXECUTION_RETURN_ERROR,
			$resultDescription
		];
	}
}
