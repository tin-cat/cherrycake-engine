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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormRange.css");
	}

	/**
	 * Builds an object and returns it
	 * 
	 * @param array $setup A hash array with the select specs
	 * @return UiComponentFormInput The object
	 */
	static function build($setup = false) {
		$UiComponentFormRange = new UiComponentFormRange($setup);
		return $UiComponentFormRange;
	}

	/**
	 * Setup keys:
	 *
	 * * type: The type of the input, normally: "text" or "password". Defaults to "text" if not specified
	 * * style: The additional styles, separated with spaces
	 * * additionalCssClasses: The additional css Class name(s)
	 * * domId: The Dom id for the UiComponentFormInput element
	 * * title: The title of the form element
	 * * name: The input name
	 * * value: The default input value
	 * * min: The minimum value of the range. Defaults to 0.
	 * * max: The maximum value of the range
	 * * step: The size of each movement step. Defaults to 1
	 * * isShowValue: Whether to show the current value of the slider or not. Defaults to true
	 * * isDisabled: Whether the input is disabled or not. Defaults to false
	 * * isAutoFocus: Whether the input must be automatically focused on page load
	 * * onChange: Javascript code to execute on change event
	 *
	 * @param array $setup A hash array with the setup keys
	 */
	function __construct($setup = false) {
		if (is_array($setup))
			while (list($key, $value) = each($setup))
				$this->$key = $value;
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