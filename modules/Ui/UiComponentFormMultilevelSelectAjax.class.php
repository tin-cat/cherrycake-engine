<?php

/**
 * UiComponentFormMultilevelSelectAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for a meta field formed by multiple selects that are dependent on each other
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormMultilevelSelectAjax extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $isCentered = false;
	protected $isDisabled = false;
	protected $onChange;
	protected $levels;
	protected $actionName; // The action name that will return the Json data to build the selects
	protected $isWrap = true;
	protected $isInnerGap = true;

	protected $saveAjaxUrl;
	protected $saveAjaxKey = false;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery",
		"UiComponentJqueryEventUe",
		"UiComponentTooltip",
		"UiComponentFormSelect",
		"UiComponentAjax",
		"UiComponentColumnStructure"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentFormMultilevelSelectAjax.js");
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

		foreach ($this->levels as $levelName => $levelData) {
			$columns[] = ["html" =>
				$e->Ui->getUiComponent("UiComponentFormSelect")->buildHtml([
					"name" => $levelName,
					"title" => $levelData["title"],
					"style" => $levelData["style"]." fullWidth"
				])
			];
		}
		reset($this->levels);

		$r .=
			"<div id=\"".$this->domId."\" class=\"UiComponentFormMultilevelSelectAjax\">".
				$e->Ui->getUiComponent("UiComponentColumnStructure")->buildHtml([
					"isWrap" => true,
					"domId" => $this->domId,
					"columns" => $columns,
					"isWrap" => $this->isWrap,
					"isInnerGap" => $this->isInnerGap
				]).
			"</div>";
		
		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentFormMultilevelSelectAjax({
				levels: ".json_encode($this->levels).",
				getDataAjaxUrl: '".$e->Actions->getAction($this->actionName)->request->buildUrl()."',
				saveAjaxUrl: '".$this->saveAjaxUrl."',
				saveAjaxKey: '".$this->saveAjaxKey."'
			});
		");

		return $r;
	}
}