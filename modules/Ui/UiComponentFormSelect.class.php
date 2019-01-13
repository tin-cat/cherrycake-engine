<?php

/**
 * UiComponentFormSelect
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentFormSelect
 *
 * A Ui component for form selects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormSelect extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $options;
	protected $defaultValue;
	protected $isDisabled = false;
	protected $isAutoFocus;
	protected $onChange;

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormSelect.css");
	}

	/**
	 * Builds an object and returns it
	 * 
	 * @param array $setup A hash array with the select specs
	 * @return UiComponentFormSelect The object
	 */
	static function build($setup = false) {
		$UiComponentFormSelect = new UiComponentFormSelect($setup);
		return $UiComponentFormSelect;
	}

	/**
	 * Setup keys:
	 *
	 * * style: The additional styles, separated with spaces
	 * * additionalCssClass: The additional css Class name(s)
	 * * domId: The Dom id for the UiComponentFormSelect element
	 * * title: The title of the form element
	 * * name: The select name
	 * * options: An array of options with the syntax:
	 *  - <value> => <The option title>
	 * * defaultValue: The default value to be selected
	 * * isDisabled: Whether the input is disabled or not. Defaults to false
	 * * isAutoFocus: Whether the input must be automatically focused on page load
	 * * onChange: Javascript code to execute on change event
	 *
	 * @param array $setup A hash array with the select specs
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
		if (is_array($setup))
			while (list($key, $value) = each($setup))
				$this->$key = $value;
		$r .=
			"<div ".
				"class=\"".
					"UiComponentFormSelect".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
			($this->title ? "<div class=\"title\">".$this->title."</div>" : null).
			"<select ".
				"class=\"".
					"UiComponentFormSelect".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClass ? " ".$this->additionalCssClass : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
				($this->isDisabled ? "disabled " : null).
				($this->isAutoFocus ? "autofocus " : null).
				($this->onChange ? "onchange=\"".$this->onChange."\" " : null).
			">";

		while (list($value, $title) = each($this->options))
			$r .=
				"<option".
					" value=\"".$value."\"".
					($this->defaultValue == $value ? " selected" : "").
				">".
					$title.
				"</option>";

		$r .=
			"</select>".
			"</div>";

		return $r;
	}
}