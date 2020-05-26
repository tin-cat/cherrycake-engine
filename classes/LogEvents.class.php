<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class that represents a list of LogEvent objects
 *
 * @package Cherrycake
 * @category Classes
 */
class LogEvents extends \Cherrycake\Items {
    protected $tableName = "log";
    protected $itemClassName = "\Cherrycake\LogEvent";

    function getItemClassName($databaseRow = false) {        
		return $databaseRow->getField("type");
	}

    /**
     * Overloads the Items::fillFromParameters method to provide an easy way to load LogEvent items on instantiating this class.
     * 
	 * @param array $setup Specifications on how to fill the LogEvents object, with the possible keys below plus any other setup keys from Items::fillFromParameters, or an array of LogEvent objects to fill the list with.
     * * type: The class name of the LogEvent objects to get.
     * * fromTimestamp: Get LogEvent items added starting on this timestamp.
     * * toTimestamp: Get LogEvent items added up to this timestamp.
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
			"chronological" => "timestamp desc"
		];
        
        if ($p["type"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "type = ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_STRING,
						"value" => $p["type"]
					]
				]
            ];
        
        if ($p["fromTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "dateAdded >= ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["fromTimestamp"]
					]
				]
            ];
        
        if ($p["toTimestamp"] ?? false)
            $p["wheres"][] = [
				"sqlPart" => "dateAdded >= ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_DATETIME,
						"value" => $p["toTimestamp"]
					]
				]
            ];

        return parent::fillFromParameters($p);
    }
}