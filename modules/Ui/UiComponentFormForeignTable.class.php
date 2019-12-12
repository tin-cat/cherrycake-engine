<?php

/**
 * UiComponentFormForeignTable
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentFormForeignTable
 *
 * A Ui component for form selects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormForeignTable extends UiComponentFormDatabaseQuery {
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
		"UiComponentFormDatabaseQuery"
	];

	function buildHtml($setup = false) {
		if (is_array($setup))
			while (list($key, $value) = each($setup))
				$this->$key = $value;
		
		return parent::buildHtml($setup);
	}
}