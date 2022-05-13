<?php

namespace Cherrycake\Modules\SystemLog;

/**
 * Class that represents a list of SystemLogEvent objects
 */
class SystemLogEvents extends \Cherrycake\Items {
    protected $tableName = "cherrycake_systemLog";
    protected $itemClassName = "\Cherrycake\Modules\SystemLogEvent";

    function getItemClassName($databaseRow = false) {
		return $databaseRow->getField("type");
	}

    /**
     * Overloads the Items::fillFromParameters method to provide an easy way to load SystemLogEvent items on instantiating this class.
     *
	 * @param array $setup Specifications on how to fill the SystemLogEvents object, with the possible keys below plus any other setup keys from Items::fillFromParameters, or an array of SystemLogEvent objects to fill the list with.
     * * type: The class name of the SystemLogEvent objects to get.
     * * fromTimestamp: Get SystemLogEvent items added starting on this timestamp.
     * * toTimestamp: Get SystemLogEvent items added up to this timestamp.
     * @return boolean True if everything went ok, false otherwise.
	 */
    function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"type" => ["default" => false],
            "fromTimestamp" => ["default" => false],
			"toTimestamp" => ["default" => false],
			"order" => ["default" => ["chronological"]]
		]);

		$p["orders"] = [
			"chronological" => "dateAdded desc"
		];

        if ($p["type"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "type = ?",
				"values" => [
					[
						"type" => \Cherrycake\Modules\Database\Database::TYPE_STRING,
						"value" => $p["type"]
					]
				]
            ];

        if ($p["fromTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "dateAdded >= ?",
				"values" => [
					[
						"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
						"value" => $p["fromTimestamp"]
					]
				]
            ];

        if ($p["toTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "dateAdded >= ?",
				"values" => [
					[
						"type" => \Cherrycake\Modules\Database\Database::TYPE_DATETIME,
						"value" => $p["toTimestamp"]
					]
				]
            ];

        return parent::fillFromParameters($p);
    }
}
