<?php

/**
 * UiComponentFormCountry
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentFormCountry
 *
 * A Ui component for form selects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormCountry extends UiComponentFormForeignTable {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $title;
	protected $name;
	protected $value;
	protected $isDisabled = false;
	protected $isAutoFocus;
	protected $onChange;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentFormForeignTable"
	];

	function buildHtml($setup = false) {
		if (is_array($setup))
			while (list($key, $value) = each($setup))
				$this->$key = $value;
		
		return parent::buildHtml($setup);
	}
}