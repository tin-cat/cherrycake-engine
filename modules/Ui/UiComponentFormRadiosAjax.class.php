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
class UiComponentFormRadiosAjax extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $isCentered = false;
	protected $isDisabled = false;
	protected $onChange;
	protected $items;

	protected $saveAjaxUrl;
	protected $saveAjaxKey = false;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery",
		"UiComponentJqueryEventUe",
		"UiComponentTooltip",
		"UiComponentFormRadios",
		"UiComponentAjax"
	];

	protected $dependentCherrycakeModules = [
		"HtmlDocument",
		"Security"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentFormRadiosAjax.js");
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
			"items" => $this->items
		];

		$r .= $e->Ui->getUiComponent("UiComponentFormRadios")->buildHtml($uiComponentFormRadiosSetup);

		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentFormRadiosAjax({
				saveAjaxUrl: '".$this->saveAjaxUrl."',
				saveAjaxKey: '".$this->saveAjaxKey."'
			});
		");

		return $r;
	}
}