<?php

/**
 * UiComponentFormSelect
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentFormSelect
 *
 * A Ui component for form selects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormSelect extends \Cherrycake\UiComponent {
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

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentFormSelect.css");
		return true;
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		if (is_array($setup))
			foreach ($setup as $key => $value)
				$this->$key = $value;

		$r =
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
			foreach ($this->items as $value => $title) {
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