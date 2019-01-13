<?php

/**
 * UiComponentBillboard
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentBillboard
 *
 * A Ui component to build fancy contents
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentBillboard extends \Cherrycake\UiComponent {
	protected $type = "limitedWidth"; // <limitedWidth|fullWidth|centered>
	protected $style;
	protected $backgroundImageUrl;
	protected $backgroundColor;
	protected $content;

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentBillboard.css");
	}

	/**
	 * Builds a fancy content block
	 * @return string The fancy content block Html
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if (!isset($this->type))
			$this->type = "limitedWidth";

		return
			"<div ".
				"class=\"".
					"UiComponentBillboard ".
					"fullScreenHeight ".
					$this->type." ".
					($this->style ? $this->style." " : null).
					($this->backgroundImageUrl ? "withBackgroundImage " : null).
				"\"".
				" style=\"".
					($this->backgroundImageUrl ? "background-image: url('".$this->backgroundImageUrl."'); " : null).
					($this->backgroundColor ? "background-color: ".$this->backgroundColor."; " : null).
				"\"".
			">".
				($this->type == "centered" ? "<div class=\"container\">" : null).
				"<div class=\"content\">".
					$this->content.
				"</div>".
				($this->type == "centered" ? "</div>" : null).
			"</div>";
	}
}