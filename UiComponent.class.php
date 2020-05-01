<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * The base class for UI component modules. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class UiComponent extends Module {
	function init() {
		if (!parent::init())
			return false;
		$this->addCssAndJavascript();
		return true;
	}
	function __toString() {
		return $this->buildHtml();
	}
}