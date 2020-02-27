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

		$domId = isset($this->DomId) ? $this->DomId : uniqid();

		$r =
			($this->href ? "<a" : "<div").
				" id=\"".$domId."\"".
			">".
			($this->href ? "</a>" : "</div>");

		$r .= "
			<script>
				$('#".$domId."').UiComponentButton({
					style: ".($this->style ?? false ? "'".$this->style."'" : "false").",
					additionalCssClasses: ".($this->additionalCssClasses ?? false ? "'".$this->additionalCssClasses."'" : "false").",
					title: ".($this->title ?? false ? json_encode($this->title) : "false").",
					tooltip: ".($this->tooltip ?? false ? json_encode($this->tooltip) : "false").",
					badge: ".($this->badge ?? false ? json_encode($this->badge) : "false").",
					iconName: ".($this->iconName ?? false ? "'".$this->iconName."'" : "false").",
					iconIsLeft: ".($this->iconIsLeft ?? false ? $this->iconIsLeft : "false").",
					iconVariant: ".($this->iconVariant ?? false ? "'".$this->iconVariant."'" : "false").",
					isTransparent: ".($this->isTransparent ?? false ? "true" : "false").",
					isInactive: ".($this->isInactive ?? false ? "true" : "false").",
					onClick: ".($this->onClick ?? false ? $this->onClick : "false").",
					href: ".($this->href ?? false ? "'".$this->href."'" : "false").",
					target: ".($this->target ?? false ? "'".$this->target."'" : "false").",
					ajaxUrl: ".($this->ajaxUrl ?? false ? "'".$this->ajaxUrl."'" : "false").",
					ajaxData: ".($this->ajaxData ?? false ? $this->ajaxData : "false").",
					ajaxOnSuccess: ".($this->ajaxOnSuccess ?? false ? $this->ajaxOnSuccess : "false").",
					ajaxOnError: ".($this->ajaxOnError ?? false ? $this->ajaxOnError : "false").",
					isNewWindow: ".($this->isNewWindow ?? false ? "true" : "false").",
					isLoading: ".($this->isLoading ?? false ? "true" : "false").",
					isCentered: ".($this->isCentered ?? false ? "true" : "false").",
					isIsolated: ".($this->isIsolated ?? false ? "true" : "false").",
					confirmationMessage: ".($this->confirmationMessage ?? false ? "'".$this->confirmationMessage."'" : "false")."
				});
			</script>
		";

		return $r;
	}
}