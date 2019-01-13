<?php

/**
 * UiComponentFormUneditable
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form elements that are intended to be shown in forms along other elements, but are not editable, just informative.
 * Uses UiComponentFormInput as base, showing a value instead of the input itself.
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormUneditable extends UiComponent {
	protected $style;
	protected $title;

	protected $dependentCherrycakeUiComponents = [
        "UiComponentFormInput",
		"UiComponentTooltip"
    ];
    
    function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormUneditable.css");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);
        $r .=
            "<div class=\"UiComponentFormUneditable".($this->style ? " ".$this->style : null).($this->additionalCssClasses ? " ".$this->additionalCssClasses : null)."\" id=\"".$this->domId."\">".
                ($this->title ? "<div class=\"title\">".$this->title."</div>" : null).
                ($this->value ? "<div class=\"value\">".$this->value."</div>" : null).
            "</div>";
		return $r;
	}
}