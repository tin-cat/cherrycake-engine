<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Class to work with text category items
 *
 * @package Cherrycake
 * @category Classes
 */
class TextCategoryItems extends \Cherrycake\Items {
	protected $tableName = "cherrycake_locale_textCategories";
	protected $itemClassName = "\Cherrycake\TextCategoryItem";
	protected $isCache = false;

	/**
	 * Finds textcategory items with the specified filters
	 * @param array $p A hash array of parameters, with the possible keys from \Cherrycake\Items::get plus the following possible keys:
	 *
	 * * order: An array of orders to apply, amongst the following ones:
	 * 	- code: Order by code
	 * 
	 * @return boolean True if everything went ok, false otherwise.
	 */
	function fillFromParameters($p = false) {
		self::treatParameters($p, [
			"order" => ["default" => ["code"]],
			"orders" => ["addArrayKeysIfNotExist" => [
				"code" => $this->tableName.".code asc, ".$this->tableName.".id desc"
			]]
		]);

		return parent::fillFromParameters($p);
	}
}