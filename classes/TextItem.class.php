<?php

/**
 * TextItem
 *
 * @package Movefy
 */

namespace Cherrycake;

/**
 * Class that represents a text
 *
 * @package Cherrycake
 * @category Classes
 */
class TextItem extends \Cherrycake\Item {
	protected $tableName = "cherrycake_locale_texts";
	protected $cacheSpecificPrefix = "CherrycakeText";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"textCategories_id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Text category Id",
			"prefix" => "#"
		],
		"code" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"formItem" => ["type" => \Cherrycake\Modules\FORM_ITEM_TYPE_STRING],
			"title" => "Code"
		],
		"description" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"formItem" => ["type" => \Cherrycake\Modules\FORM_ITEM_TYPE_STRING],
			"title" => "Description"
		],
		"text" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_TEXT,
			"formItem" => ["type" => \Cherrycake\Modules\FORM_ITEM_TYPE_TEXT],
			"title" => "Text",
			"isMultiLanguage" => true
		]
	];

	/**
	 * Initializes the Item. Intended to be overloaded to perform any additional actions that must be done just after the Item is loaded with data
	 *
	 * @return boolean True on success, false on failure
	 */
	function init() {
		global $e;
		foreach ($e->Locale->getConfig("availableLanguages") as $language)
			$this->fields["text".$language] = [
				"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
				"title" => $e->Locale->getLanguageName($language)." text"
			];
		return parent::init();
	}
}