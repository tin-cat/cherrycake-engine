<?php


/**
 * The base class for UI component modules. Intented to be overloaded by specific functionality classes
 *
 * @package Cherrycake
 * @category Modules
 */
class UiComponent extends Cherrycake\Module {
	function init() {
		$this->dependentCoreModules[] = "Css";
		$this->dependentCoreModules[] = "Javascript";
		if (!parent::init())
			return false;
		$this->addCssAndJavascript();
		return true;
	}
	function __toString() {
		return $this->buildHtml();
	}
}
