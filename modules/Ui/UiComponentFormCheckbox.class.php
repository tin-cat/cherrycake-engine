<?php

/**
 * UiComponentFormCheckbox
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form checkboxes
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormCheckbox extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $value = 1;
	protected $isChecked = false;
	protected $description;
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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormCheckbox.css");
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
					"UiComponentFormCheckbox".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
				($this->title ? "<div class=\"title\">".$this->title."</div>" : null).
				"<input ".
					"type=\"checkbox\" ".
					($this->name ? "name=\"".$this->name."\" " : null).
					($this->value ? "value=\"".htmlspecialchars($this->value)."\" " : null).
					($this->isDisabled ? "disabled " : null).
					($this->isAutoFocus ? "autofocus " : null).
					($this->onChange ? "onchange=\"".$this->onChange."\" " : null).
					($this->isChecked ? "checked " : null).
				"/>".
				($this->description ? "<div class=\"description\">".$this->description."</div>" : null).
			"</div>";
	}
}