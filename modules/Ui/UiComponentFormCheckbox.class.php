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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormCheckbox.css");
	}

	/**
	 * Builds an object and returns it
	 * 
	 * @param array $setup A hash array with the select specs
	 * @return UiComponentFormInput The object
	 */
	static function build($setup = false) {
		$UiComponentFormCheckbox = new UiComponentFormCheckbox($setup);
		return $UiComponentFormCheckbox;
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
	 * * value: The default input value. Defaults to 1.
	 * * isChecked: Whether the checkbox must be checked by default. Defaults to false
	 * * description: The description text to be placed next to the checkbox, if desired.
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