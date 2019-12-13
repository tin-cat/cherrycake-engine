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
	protected $title;
	protected $name;
	protected $items;
	protected $value;
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
				($this->name ? " name=\"".$this->name."\"" : null).
				($this->isDisabled ? "disabled " : null).
				($this->isAutoFocus ? "autofocus " : null).
				($this->onChange ? "onchange=\"".$this->onChange."\" " : null).
			">";

		if (is_array($this->items)) {
			while (list($value, $title) = each($this->items)) {
				$r .=
					"<option".
						" value=\"".$value."\"".
						($this->value == $value ? " selected" : "").
					">".
						$title.
					"</option>";
			}
		}

		$r .=
			"</select>".
			"</div>";

		return $r;
	}
}