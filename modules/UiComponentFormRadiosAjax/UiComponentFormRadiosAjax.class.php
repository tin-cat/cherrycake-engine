<?php

/**
 * UiComponentFormRadiosAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for a form radios group that sends the data independently via Ajax
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormRadiosAjax extends \Cherrycake\UiComponent {
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
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentFormRadiosAjax.js");
		return true;
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
		
		$uiComponentFormRadiosSetup = [
			"name" => $this->name,
			"title" => $this->title,
			"style" => $this->style,
			"additionalCssClasses" => $this->additionalCssClasses,
			"domId" => $this->domId,
			"items" => $this->items,
			"value" => $this->value,
		];

		$r .= $e->UiComponentFormRadios->buildHtml($uiComponentFormRadiosSetup);

		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentFormRadiosAjax({
				saveAjaxUrl: '".$this->saveAjaxUrl."',
				saveAjaxKey: '".$this->saveAjaxKey."'
			});
		");

		return $r;
	}
}