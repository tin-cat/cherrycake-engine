<?php

/**
 * UiComponentTable
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentTable
 *
 * A Ui component for tables
 *
 *  Configuration example for UiComponenttable.config.php:
 * <code>
 *  $UiComponentTableConfig = [
 *      "responsiveBreakpoint" => [
 *      		"normal" => 980, // Breakpoint where table will be shrinked to fit in small screens
 *      		"mini" => 500 // Breakpoint where table will be further shrinked to fit in really small screens
 *      	]
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTable extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $header;
	protected $data;

	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"responsiveBreakpoint" => [
			"normal" => 980,
			"mini" => 500
		]
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentTable.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentTable.js");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys
	 * * style: An additional style name
	 * * additionalCssClasses: Additional css classes
	 * * domId: Optional dom Id
	 * 
	 * * columns: A hash array for the columns of the table, where the key is a unique column identifier and the value is an array as follows:
	 * * * title: The title of the column
	 * * * style
	 * * * additionalCssClasses
	 * * rows: A hash array for the rows of the table, where each item has a unique key identified and each value is a hash array where each key matched the column's key, and the value is an array as follows:
	 * * * value: The value for the cell
	 * 
	 *  @return string The HTML of the table. Null if no table could be generated.
	 */
	function buildHtml($setup = false) {
		global $e;

		$this->treatParameters($setup, [
			"domId" => ["default" => uniqid()],
			"style" => ["default" => false],
			"additionalCssClasses" => ["default" => false],
			"title" => ["default" => false],
		]);
		$this->setProperties($setup);

		$e->loadCoreModule("HtmlDocument");

		// Build HTML table
		$html = "<div id=\"".$this->domId."\"></div>";

		$e->HtmlDocument->addInlineJavascript("$('#".$this->domId."').UiComponentTable(".json_encode($setup).");");

		return $html;
	}
}