<?php

/**
 * UiComponentFormRange
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form ranges
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormRange extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $value;
	protected $min = 0;
	protected $max;
	protected $step = 1;
	protected $isShowValue = true;
	protected $isDisabled = false;
	protected $onChange;
	protected $title;

	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormRange.css");
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

		if ($this->error) {
			global $e;

			$e->loadCoreModule("HtmlDocument");

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
		
		return
			"<div ".
				"class=\"".
					"UiComponentFormRange".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
			($this->title ? "<div class=\"title\">".$this->title."</div>" : null).
			($this->isShowValue && !$this->onChange ? "<output name=\"".$this->name."Value\" id=\"".$this->domId."Value\">".$this->value."</output>" : null).
				"<input ".
					"type=\"range\" ".
					($this->name ? "name=\"".$this->name."\" " : null).
					($this->value ? "value=\"".htmlspecialchars($this->value)."\" " : null).
					($this->min ? "min=\"".htmlspecialchars($this->min)."\" " : null).
					($this->max ? "max=\"".htmlspecialchars($this->max)."\" " : null).
					($this->step ? "step=\"".htmlspecialchars($this->step)."\" " : null).
					($this->isDisabled ? "disabled " : null).
					($this->isAutoFocus ? "autofocus " : null).
					($this->onChange ? "onchange=\"".$this->onChange."\" " : null).
					"id=\"".$this->domId."Input\" ".
					($this->isShowValue ? "oninput=\"this.form.".$this->name."Value.value=this.value\" " : null).
				"/>".
			"</div>";
	}
}