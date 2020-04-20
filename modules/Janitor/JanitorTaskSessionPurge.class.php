<?php

/**
 * JanitorTaskSession
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * JanitorTaskSession
 *
 * A JanitorTask to maintain the Session module.
 * Purges sessions older than the given seconds. A different configuration is given for differentiating the purging sessions without any data stored (sessions that haven't been used in any way) between sessions with data stored (sessions that have been actually used somehow)
 * To avoid unnecessary database cluttering and minimize session id collision risk.
 *
 * @package Cherrycake
 * @category Classes
 */
class JanitorTaskSessionPurge extends JanitorTask
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
		"periodicityEachSeconds" => 86400, // (86400 = 1 day)
		"purgeSessionsWithoutDataOlderThanSeconds" => 86400, // Sessions older than this seconds without any data will be deleted from the database, since they haven't been actually used (most likely, visits that have bounced the site). (86400 = 1 day)
		"purgeSessionsWithDataOlderThanSeconds" => 31536000 // Sessions older than this seconds _with_ data will be deleted from the database. This should be a lot higher (specially when isSessionRenew in session.config.php for Session module is true) than the session duration in session.config.php (sessionDuration config key), in order to remove sessions that have been used but lasted too long. This is intended mostly to avoid cluttering the database with too many sessions, and to avoid potential session id collisions, which can represent a security risk. (31536000 = 365 days)
	];

	/**
	 * @var string $name The name of the task
	 */
	protected $name = "Session purge";

	/**
	 * @var string $description The description of the task
	 */
	protected $description = "Purges discarded sessions from the Session module";

	/**
	 * getDebugInfo
	 *
	 * Returns a hash array with debug info for this task. Can be overloaded to return additional info, on which case the specific results should be merged with this results with array_merge(parent::getDebugInfo(), <specific debug info array>)
	 *
	 * @return array Hash array with debug info for this task
	 */
	function getDebugInfo() {
		return array_merge(parent::getDebugInfo(), [
			"PurgeSessionsWithoutDataOlderThanSeconds" => $this->getConfig("purgeSessionsWithoutDataOlderThanSeconds"),
			"PurgeSessionsWithDataOlderThanSeconds" => $this->getConfig("purgeSessionsWithDataOlderThanSeconds")
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
		$e->loadCoreModule("Session");

		$databaseProviderName = $e->Session->getConfig("sessionDatabaseProviderName");

		// Purge sessions older than PurgeSessionsWithoutDataOlderThanSeconds without data
		$result = $e->Database->$databaseProviderName->prepareAndExecute(
			"select count(*) as numberOf from ".$e->Session->getConfig("sessionTableName")." where creationDate < ? and data is null",
			[
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
					"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeSessionsWithoutDataOlderThanSeconds"))
				]
			]
		);

		if (!$result)
			return [
				\Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_ERROR,
				"Could not query the database"
			];

		$row = $result->getRow();
		$numberOfSessionsToPurgeWithoutData = $row->getField("numberOf");

		if ($numberOfSessionsToPurgeWithoutData > 0) {
			$result = $e->Database->$databaseProviderName->prepareAndExecute(
				"delete from ".$e->Session->getConfig("sessionTableName")." where creationDate < ? and data is null",
				[
					[
						"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
						"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeSessionsWithoutDataOlderThanSeconds"))
					]
				]
			);

			if (!$result)
				return [
					\Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_ERROR,
					"Could not delete sessions from the database"
				];
		}


		// Purge sessions older than PurgeSessionsWithoutDataOlderThanSeconds with data
		$result = $e->Database->$databaseProviderName->prepareAndExecute(
			"select count(*) as numberOf from ".$e->Session->getConfig("sessionTableName")." where creationDate < ? and data is not null",
			[
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
					"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeSessionsWithDataOlderThanSeconds"))
				]
			]
		);

		if (!$result)
			return [
				\Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_ERROR,
				"Could not query the database"
			];

		$row = $result->getRow();
		$numberOfSessionsToPurgeWithData = $row->getField("numberOf");

		if ($numberOfSessionsToPurgeWithData > 0) {
			$result = $e->Database->$databaseProviderName->prepareAndExecute(
				"delete from ".$e->Session->getConfig("sessionTableName")." where creationDate < ? and data is not null",
				[
					[
						"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
						"value" => date("Y-n-j H:i:s", $baseTimestamp - $this->getConfig("purgeSessionsWithDataOlderThanSeconds"))
					]
				]
			);

			if (!$result)
				return [
					\Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_ERROR,
					"Could not delete sessions from the database"
				];
		}


		return [
			\Cherrycake\Modules\JANITORTASK_EXECUTION_RETURN_OK,
			[
				"Sessions older than ".$this->getConfig("purgeSessionsWithoutDataOlderThanSeconds")." seconds without data purged" => $numberOfSessionsToPurgeWithoutData,
				"Sessions older than ".$this->getConfig("purgeSessionsWithDataOlderThanSeconds")." seconds with data purged" => $numberOfSessionsToPurgeWithData
			]
		];
	}
}