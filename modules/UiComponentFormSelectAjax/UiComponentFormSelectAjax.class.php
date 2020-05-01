<?php

/**
 * UiComponentFormSelectAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * A Ui component for a select that sends the data independently via Ajax
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormSelectAjax extends \Cherrycake\UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $isCentered = false;
	protected $isDisabled = false;
	protected $onChange;
	protected $items;

	protected $saveAjaxUrl;
	protected $saveAjaxKey = false;

	protected $dependentCoreModules = [
		"UiComponentJquery",
		"UiComponentJqueryEventUe",
		"UiComponentTooltip",
		"UiComponentFormRadios",
		"UiComponentAjax",
		"HtmlDocument",
		"Security"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentFormSelectAjax.js");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		global $e;

		$this->setProperties($setup);

		if (!$this->domId)
			$this->domId = uniqid();
		
		$uiComponentFormSelectSetup = [
			"name" => $this->name,
			"title" => $this->title,
			"style" => $this->style,
			"additionalCssClasses" => $this->additionalCssClasses,
			"domId" => $this->domId,
			"items" => $this->items,
			"value" => $this->value,
		];

		$r = $e->UiComponentFormSelect->buildHtml($uiComponentFormSelectSetup);

		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentFormSelectAjax({
				saveAjaxUrl: '".$this->saveAjaxUrl."',
				saveAjaxKey: '".$this->saveAjaxKey."'
			});
		");

		return $r;
	}
}