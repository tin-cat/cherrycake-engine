<?php

namespace Cherrycake\Stats;

/**
 * Class that represents a list of StatsEvent objects
 */
class StatsEvents extends \Cherrycake\Items {
    protected $tableName = "cherrycake_stats";
	protected $itemClassName = "\Cherrycake\StatsEvent";

	function getItemClassName($databaseRow = false) {
		return $databaseRow->getField("type");
	}

    /**
	 * @param array $setup Specifications on how to fill the StatsEvents object, with the possible keys below plus any other setup keys from Items::fillFromParameters, or an array of StatsEvent objects to fill the list with.
     * * type: The class name of the StatsEvent objects to get.
     * * fromTimestamp: The starting timestamp for the time frame to get StatsEvent objects from.
     * * toTimestamp: The finishing timestamp for the time frame to get StatsEvent objects from.
	 */
    function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"type" => ["default" => false],
            "fromTimestamp" => ["default" => false],
			"toTimestamp" => ["default" => false],
			"order" => ["chronological"]
		]);

		$p["orders"] = [
			"chronological" => "timestamp desc"
		];

        if ($p["type"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "type = ?",
				"values" => [
					[
						"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_STRING,
						"value" => $p["type"]
					]
				]
            ];

        if ($p["fromTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "timestamp >= ?",
				"values" => [
					[
						"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["fromTimestamp"]
					]
				]
            ];

        if ($p["toTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "timestamp >= ?",
				"values" => [
					[
						"type" => \Cherrycake\Database\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["toTimestamp"]
					]
				]
            ];

        return parent::fillFromParameters($p);
    }
}
