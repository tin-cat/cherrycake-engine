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
		if (!$this->isConfig("cssSetName"))
			$this->setConfig("cssSetName", "uiComponents");
		if (!$this->isConfig("javascriptSetName"))
			$this->setConfig("javascriptSetName", "uiComponents");
		$this->addCssAndJavascript();
		return true;
	}
	function __toString() {
		return $this->buildHtml();
	}
}