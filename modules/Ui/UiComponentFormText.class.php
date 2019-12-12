<?php

/**
 * UiComponentFormText
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form inputs
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormText extends UiComponent {
	protected $domId;
	protected $style;
	protected $name;
	protected $value;
	protected $columns;
	protected $rows = 10;
	protected $placeHolder;
	protected $isDisabled = false;
	protected $isAutoComplete = false;
	protected $isAutoFocus = false;
	protected $isAutocorrect = false;
	protected $isSpellCheck = false;
	protected $onChange;
	protected $title;
	protected $isAjaxOnChange;
	protected $ajaxOnChangeDelay;
	protected $ajaxSaveUrl;

	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentTooltip"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormText.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentFormText.js");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if (!$this->domId)
				$this->domId = uniqid();
			
		$r .= "<div id=\"".$this->domId."\"></div>";
		$r .= "
			<script>
				$('#".$this->domId."').UiComponentFormText(".json_encode([
					"type" => $this->type,
					"style" => $this->style,
					"name" => $this->name,
					"value" => $this->value,
					"size" => $this->size,
					"maxLength" => $this->maxLength,
					"placeHolder" => $this->placeHolder,
					"isDisabled" => $this->isDisabled,
					"isAutoComplete" => $this->isAutoComplete,
					"isAutoFocus" => $this->isAutoFocus,
					"isAutocapitalize" => $this->isAutocapitalize,
					"isAutocorrect" => $this->isAutocorrect,
					"isSpellCheck" => $this->isSpellCheck,
					"onChange" => $this->onChange,
					"title" => $this->title,
					"isSubmitOnEnter" => $this->isSubmitOnEnter,
					"isAjaxOnChange" => $this->isAjaxOnChange,
					"ajaxSaveUrl" => $this->ajaxSaveUrl,
					"ajaxOnChangeDelay" => $this->ajaxOnChangeDelay
				]).");
			</script>
		";

		if ($this->error) {
			global $e;

			$e->loadCherrycakeModule("HtmlDocument");

			$e->HtmlDocument->addInlineJavascript("
				$('#".$this->domId."').UiComponentTooltip({
					isOpenOnInit: true,
					isCloseWhenOthersOpen: false,
					style: 'styleSimple styleWarning',
					content: ".json_encode(
						UiComponentTooltip::buildContentItem(
							UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE,
							[
								"title" => $this->error
							]
						)
					).",
					position: 'rightTop',
					isTapToPopupOnSmallScreens: true
				});
			");
		}

		return $r;
	}
}