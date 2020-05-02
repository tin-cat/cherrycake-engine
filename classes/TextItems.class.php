<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class to work with text items
 *
 * @package Cherrycake
 * @category Classes
 */
class TextItems extends \Cherrycake\Items {
	protected $tableName = "cherrycake_locale_texts";
	protected $itemClassName = "\Cherrycake\TextItem";
	protected $isCache = false;

	/**
	 * Finds textcategory items with the specified filters
	 * @param array $p A hash array of parameters, with the possible keys from \Cherrycake\Items::get plus the following possible keys:
	 *                 - textCategoryId: <integer|false> Get only textItems from the given texts category id. If left to false, it gets texts from all categories.
	 *                 - order: An array of orders to apply, amongst the following ones:
	 *	                - code: Order by code
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"textCategoryId" => ["default" => false],
			"order" => ["default" => ["code"]],
			"orders" => ["addArrayKeysIfNotExist" => [
				"code" => $this->tableName.".code asc, ".$this->tableName.".id desc"
			]]
		]);

		// Build query wheres
		if ($p["textCategoryId"])
			$p["wheres"][] = [
				"sqlPart" => "textCategories_id = ?",
				"values" => [
					[
						"type" => \Cherrycake\DATABASE_FIELD_TYPE_INTEGER,
						"value" => $p["textCategoryId"]
					]
				]
			];

		return parent::fillFromParameters($p);
	}
}