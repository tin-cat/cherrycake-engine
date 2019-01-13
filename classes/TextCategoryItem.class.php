<?php

/**
 * TextCategoryItem
 *
 * @package Movefy
 */

namespace Cherrycake;

/**
 * Class that represents a text category
 *
 * @package Cherrycake
 * @category Classes
 */
class TextCategoryItem extends \Cherrycake\Item {
	protected $tableName = "cherrycake_locale_textCategories";
	protected $cacheSpecificPrefix = "CherrycakeTextCategory";

	protected $fields = [
		"id" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
			"title" => "Id",
			"prefix" => "#"
		],
		"code" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Category code"
		],
		"description" => [
			"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING,
			"title" => "Description"
		]
	];
}