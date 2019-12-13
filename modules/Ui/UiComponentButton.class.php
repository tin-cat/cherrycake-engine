<?php

/**
 * UiComponentButton
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentButton
 *
 * A Ui component for buttons
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentButton extends UiComponent {
	protected $domId;
	protected $style;
	protected $title;
	protected $badge;
	protected $iconName;
	protected $iconIsLeft = true;
	protected $iconVariant = "white";
	protected $href;
	protected $target = false;
	protected $ajaxUrl = false;
	protected $ajaxData = false;
	protected $ajaxOnSuccess = false;
	protected $ajaxOnerror = false;
	protected $isNewWindow = false;
	protected $isTransparent = false;
	protected $isInactive = false;
	protected $isCentered = false;
	protected $isIsolated = false;
	protected $confirmationMessage = false;
	public $onClick;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentJqueryEventUe"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentButton.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentButton.js");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		$domId = $this->DomId ? $this->DomId : uniqid();

		$r .=
			($this->href ? "<a" : "<div").
				" id=\"".$domId."\"".
			">".
			($this->href ? "</a>" : "</div>");

		$r .= "
			<script>
				$('#".$domId."').UiComponentButton({
					style: ".($this->style ? "'".$this->style."'" : "false").",
					additionalCssClasses: ".($this->additionalCssClasses ? "'".$this->additionalCssClasses."'" : "false").",
					title: ".($this->title ? json_encode($this->title) : "false").",
					tooltip: ".($this->tooltip ? json_encode($this->tooltip) : "false").",
					badge: ".($this->badge ? json_encode($this->badge) : "false").",
					iconName: ".($this->iconName ? "'".$this->iconName."'" : "false").",
					iconIsLeft: ".($this->iconIsLeft ? $this->iconIsLeft : "false").",
					iconVariant: ".($this->iconVariant ? "'".$this->iconVariant."'" : "false").",
					isTransparent: ".($this->isTransparent ? "true" : "false").",
					isInactive: ".($this->isInactive ? "true" : "false").",
					onClick: ".($this->onClick ? $this->onClick : "false").",
					href: ".($this->href ? "'".$this->href."'" : "false").",
					target: ".($this->target ? "'".$this->target."'" : "false").",
					ajaxUrl: ".($this->ajaxUrl ? "'".$this->ajaxUrl."'" : "false").",
					ajaxData: ".($this->ajaxData ? $this->ajaxData : "false").",
					ajaxOnSuccess: ".($this->ajaxOnSuccess ? $this->ajaxOnSuccess : "false").",
					ajaxOnError: ".($this->ajaxOnError ? $this->ajaxOnError : "false").",
					isNewWindow: ".($this->isNewWindow ? "true" : "false").",
					isLoading: ".($this->isLoading ? "true" : "false").",
					isCentered: ".($this->isCentered ? "true" : "false").",
					isIsolated: ".($this->isIsolated ? "true" : "false").",
					confirmationMessage: ".($this->confirmationMessage ? "'".$this->confirmationMessage."'" : "false")."
				});
			</script>
		";

		return $r;
	}
}