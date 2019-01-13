<?php

/**
 * UiComponentImage
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentImage
 *
 * A Ui component to represent images based on a given Image object
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentImage extends UiComponent {
	protected $image;
	protected $imageSizeName;
	protected $isHd = true;
	protected $method = "img";
	protected $domId;
	protected $additionalCssClasses;
	protected $width;
	protected $height;

	/**
	 * build
	 *
	 * Builds an image HTML representation with the given config specs.
	 *
	 * Setup keys:
	 *
	 * * image: The Image object
	 * * imageSizeName: The image size to use
	 * * isHd: Whether to use an Hd version of the image if available. Defaults to false.
	 * * method: One of the following: (Defaults to "img")
	 *      "img" Builds an IMG tag
	 *      "div" Builds a div element with the image as a background
	 *      "css" Builds the CSS code to put the image as a background
	 * * domId: The Dom id for the element
	 * * additionalCssClass: Additional CSS class(es) for the element
	 * * width: The optional width to force the image to.
	 * * height: Same as width, but for height.
	 *
	 * @todo Automatically detect if the client's browsers is capable of HD images, and make isHd defaulted to that setting
	 *
	 * @param array $setup A hash array with the image representation specs
	 * @return string The Html
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		switch ($this->method) {
			case "div":
			case "a":
			case "css":
				$css = "background-image: url('".$this->image->getUrl($this->imageSizeName, $this->isHd)."');";
				break;
		}

		switch ($this->method) {
			case "img":
				$r .=
					"<img".
						" class=\"UiComponentImage".($this->additionalCssClass ? " ".$this->additionalCssClass : "")."\"".
						($this->domId ? " id=\"".$this->domId."\"" : "").
						" src=\"".$this->image->getUrl($this->imageSizeName, $this->isHd)."\"".
					" />";
				break;

			case "div":
				$r .=
					"<div".
						" class=\"UiComponentImage".($this->additionalCssClass ? " ".$this->additionalCssClass : "")."\"".
						($this->domId ? " id=\"".$this->domId."\"" : "").
						" style=\"".
							$css.
						"\"".
					"></div>";
				break;

			case "a":
				$r .=
					"<a".
						" class=\"UiComponentImage".($this->additionalCssClass ? " ".$this->additionalCssClass : "")."\"".
						($this->domId ? " id=\"".$this->domId."\"" : "").
						($this->linkUrl ? " href=\"".$this->linkUrl."\"" : "").
						" style=\"".
							$css.
						"\"".
					"></a>";
				break;

			case "css":
				return $css;
				break;
		}

		return $r;
	}
}