<?php

/**
 * UiComponentButtonSwitchAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentButtonSwitchAjax
 *
 * A Ui component for button that switch between two states and send an ajax query when switching
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentButtonSwitchAjax extends \Cherrycake\UiComponent {
	protected $defaultState;
	protected $states;
	protected $domId;

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentButtonSwitchAjax.js");
		return true;
	}

	function buildHtml($setup = false) {
		global $e;

		$this->setProperties($setup);

		if (!is_array($this->states))
			return;

		if (!isset($this->defaultState))
			$this->defaultState = array_keys($this->states)[0];
		
		if (!$this->domId)
			$this->domId = uniqid();

		$e->loadCoreModule("HtmlDocument");

		$r = "<div id=\"".$this->domId."\"></div>";

		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentButtonSwitchAjax({
				defaultState: ".json_encode($this->defaultState).",
				states: ".json_encode($this->states)."
			});
		");

		return $r;
	}
}