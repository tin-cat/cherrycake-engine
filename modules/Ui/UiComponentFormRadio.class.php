<?php

/**
 * UiComponentFormRadio
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form radio buttons
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormRadio extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $value;
	protected $isChecked = false;
	protected $title;
	protected $subTitle;
	protected $isDisabled = false;
	protected $onChange;

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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormRadio.css");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if ($this->error) {
			global $e;

			if (!$this->domId)
				$this->domId = uniqid();

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
		
		return
			"<div ".
				"class=\"".
					"UiComponentFormRadio".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
				"<input ".
					"type=\"radio\" ".
					($this->name ? "name=\"".$this->name."\" " : null).
					($this->value !== false ? "value=\"".htmlspecialchars($this->value)."\" " : null).
					($this->isDisabled ? "disabled " : null).
					($this->isAutoFocus ? "autofocus " : null).
					($this->onChange ? "onchange=\"".$this->onChange."\" " : null).
					($this->isChecked ? "checked " : null).
				"/>".
				"<div>".
					($this->title ? "<div class=\"title\">".$this->title."</div>" : null).
					($this->subTitle ? "<div class=\"subTitle\">".$this->subTitle."</div>" : null).
				"</div>".
			"</div>";
	}
}