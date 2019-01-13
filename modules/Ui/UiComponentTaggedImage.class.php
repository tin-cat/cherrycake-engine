<?php

/**
 * UiComponentTaggedImage
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentTaggedImage
 *
 * A Ui component that shows an image with interactive hotspot tags
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTaggedImage extends UiComponent
{
	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentTaggedImage.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentTaggedImage.js");
	}

	/**
	 * buildJavascriptCall
	 *
	 * Builds the javascript call that triggers the tagged image on the given DOM element, with the given image and setup
	 *
	 * Setup keys:
	 *
	 * * imageSizeName: If the passed $image has sizes, the size name to use
	 * * scaling: The technique to make the image fit, "cover" or "fit"
	 * * tags: An array of TaggedImageTag objects to be rendered on top of the image as hotspots
	 * * autoSetScalingToFitHiddenPixelsThreshold: If set, the image scaling will be set automatically to "fit" when this amount of pixels is being hidden on the screen, to avoid images with weird proportions to look strange
	 * * cssClassOnTagOver: Optional, The Css class name that will be added/removed to the tag element when hovering the tag. Defaults to "visible"
	 * * cssClassForLinkedElementsOnTagOver: Optional, the Css class name that will be added/removed to the linked elements when hovering the tag. Defaults to "highlight"
	 *
	 * @param Image $image The image object
	 * @param string $domId The dom id of the element on which to render the tagged image
	 * @param array $setup Setup options
	 * @return string The HTML
	 */
	function buildJavascriptCall($image, $domId, $setup = false) {
		// Build the tags array
		if (is_array($setup["tags"]))
			foreach ($setup["tags"] as $tag) {
				$position = $tag->getPositionPercentage();
				$tags[] = [
					"x" => $position["x"],
					"y" => $position["y"],
					"html" => $tag->getHtml(),
					"domId" => $tag->getDomId(),
					"linkedDomSelector" => $tag->getLinkedDomSelector()
				];
			}

		$r =
			"$('#".$domId."').TaggedImage({".
				"isDebug: ".($setup["isDebug"] ? "true" : "false").
				",imageUrl: '".$image->getUrl($setup["imageSizeName"])."'".
				",imageWidth: ".$image->getWidth($setup["imageSizeName"]).
				",imageHeight: ".$image->getHeight($setup["imageSizeName"]).
				($setup["scaling"] ? ",scaling: '".$setup["scaling"]."'" : null).
				($setup["autoSetScalingToFitHiddenPixelsThreshold"] ? ",autoSetScalingToFitHiddenPixelsThreshold: ".$setup["autoSetScalingToFitHiddenPixelsThreshold"] : null).
				($setup["cssClassOnTagOver"] ? ",cssClassOnTagOver: '".$setup["cssClassOnTagOver"]."'" : null).
				($setup["cssClassForLinkedElementsOnTagOver"] ? ",cssClassForLinkedElementsOnTagOver: '".$setup["cssClassForLinkedElementsOnTagOver"]."'" : null).
				", tags: ".json_encode($tags).
			"});";
		return $r;
	}
}

/**
 * TaggedImageTag
 *
 * A class representing a tag over the image ofor being used on the UiComponentTaggedImage class. Intended to be overloaded.
 *
 * @package Cherrycake
 * @category Classes
 */
class TaggedImageTag
{
	/**
	 * getPositionPercentage
	 *
	 * Intended to be overloaded
	 *
	 * @return array A hash array with the keys "x" and "y"; two float values containing the X and Y percentual position of this Tag over the image
	 */
	function getPositionPercentage() {
	}

	/**
	 * getHtml
	 *
	 * Intended to be overloaded.
	 *
	 * @return string The HTML to be rendered for this tag over the image
	 */
	function getHtml() {
	}

	/**
	 * getDomId
	 *
	 * Intended to be overloaded
	 *
	 * @return string The DOM Id for this tag, must be unique
	 */
	function getDomId() {
	}

	/**
	 * getLinkedDomSelector
	 *
	 * Returns a DOM selector to match the DOM elements that should be highlighted when hovering this tag. Those same elements will have a hover event added that will in turn highlight this tag. Intended to be overloaded
	 *
	 * @return string The DOM selector
	 */
	function getLinkedDomSelector() {
	}
}