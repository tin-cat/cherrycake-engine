<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to create a menu option, normally used in conjunction with other higher level UiComponents like UiComponentPanel
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentMenuOption extends UiComponent {
	protected $dependentCherrycakeUiComponents = [
        "UiComponentIcons"
	];
	
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentMenuOption.css");
	}

	/**
	 * @var boolean $isSelected Whether this component is selected or not
	 */
	public $isSelected = false;

	/**
	 * Sets whether this component is selected or not
	 * 
	 * @param boolean $isSelected Whether this component is selected or not
	 */
	function setSelected($isSelected = true) {
		$this->isSelected = $isSelected;
	}

	/**
	 * @return boolean Whether this option is selected or not
	 */
	function isSelected() {
		return $this->isSelected;
	}

	/**
	 * Builds the HTML of the menu option and returns it.
	 *
	 * @param array $setup A hash array of setup keys the building the panel
	 * @return string The HTML.
	 */
	function buildHtml($setup = false) {
        global $e;

		$this->setProperties($setup);

		$r =
			($this->href ?? false ? "<a href=\"".$this->href."\"".($this->isNewWindow ?? false ? " target=\"_newwindow\"" : null) : "<div").
				" class=\"".
					"UiComponentMenuOption".
					($this->isSelected ?? false ? " selected" : null).
				"\"".
			">".
				($this->iconName ?? false ? "<div class=\"UiComponentIcon ".$this->iconVariant." ".$this->iconName."\"></div>" : "<div class=\"letterIcon\">".substr($this->title, 0, 1)."</div>").
				($this->title ?? false ? "<div class=\"title\">".$this->title."</div>" : null).
				($this->isDropdownArrow ?? false && $this->isDropdownArrow ? "<div class=\"dropdownArrow\"></div>" : null).
			($this->href ?? false ? "</a>" : "</div>");

		return $r;
	}
}