<?php

/**
 * UiComponentMenu
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to create menus
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentMenu extends UiComponent {
	/**
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentMenu.css");
	}

	/**
	 * @var array $options The menu options
	 */
	protected $options;

	/**
	 * @var string $selectedOption The selected option key
	 */
	protected $selectedOption;

	/**
	 * @var string $selectedSecondLevelOption The second level selected option key
	 */
	protected $selectedSecondLevelOption;

	/**
	 * @var string $style The style of the menu
	 */
	protected $style;

	/**
	 * Adds a bunch of options to the menu
	 *
	 * @param array $options A hash array with one or more items in the form of $key => $optionSetup, as expected by the addOption method
	 */
	function addOptions($options) {
		while (list($key, $optionSetup) = each($options))
			$this->addOption($key, $optionSetup);
	}

	/**
	 * Adds an option to the menu
	 *
	 * @param string $key The key of the option, for further reference.
	 * @param array $optionSetup A hash array to setup this option, with the following keys
	 *  - order: The order of the option in relation to other options, a numeric value
	 *  - domId: The optional dom Id for the option element
	 *  - title: The title of the option
	 *  - iconName: The icon name, if any
	 *  - iconVariant: The icon variant, if any
	 *  - href: The Href of the option
	 *  - onClick: The javascript code to execute on click. Overrides "href"
	 *  - isSelected: Whether to set this option as the selected one. Defaults to false.
	 *  - additionalCssClass: Additional CSS classes to add to this option, if needed.
	 */
	function addOption($key, $optionSetup) {
		$this->options[$key] = $optionSetup;
		if ($optionSetup["secondLevelOptions"])
			$this->addSecondLevelOptions($key, $optionSetup["secondLevelOptions"]);
		if ($optionSetup["isSelected"])
			$this->setSelectedOption($key);
	}

	/**
	 * Sets the selected option of the menu
	 *
	 * @param string $key The option key to be selected
	 */
	function setSelectedOption($key) {
		$this->selectedOption = $key;
	}

	/**
	 * Adds a bunch of options to the menu
	 *
	 * @param string $firstLevelKey The key of the first level option to which to add the options
	 * @param array $options A hash array with one or more items in the form of $key => $optionSetup, as expected by the addOption method
	 */
	function addSecondLevelOptions($firstLevelKey, $options) {
		while (list($key, $optionSetup) = each($options))
			$this->addSecondLevelOption($firstLevelKey, $key, $optionSetup);
	}

	/**
	 * Adds a secon level option to the specified option
	 *
	 * @param string $firstLevelKey The key of the first level option to which to add the option
	 * @param string $key The key of the option to which to add a second level option
	 * @param array $optionSetup A hash array to setup this option, with the following keys
	 *  - order: The order of the option in relation to other options, a numeric value
	 *  - domId: The optional dom Id for the option element
	 *  - title: The title of the option
	 *  - iconName: The icon name, if any
	 *  - iconVariant: The icon variant, if any
	 *  - href: The Href of the option
	 *  - onClick: The javascript code to execute on click. Overrides "href"
	 *  - isSelected: Whether to set this option as the selected one. Defaults to false.
	 *  - additionalCssClass: Additional CSS classes to add to this option, if needed.
	 */
	function addSecondLevelOption($firstLevelKey, $key, $optionSetup) {
		if (!isset($this->options[$firstLevelKey]))
			return;;
		$this->options[$firstLevelKey]["secondLevelOptions"][$key] = $optionSetup;
		if ($optionSetup["isSelected"])
			$this->setSecondLevelSelectedOption($key);
	}

	/**
	 * Sets the selected second level option of the menu
	 *
	 * @param string $key The second level option key to be selected
	 */
	function setSecondLevelSelectedOption($key) {
		$this->selectedSecondLevelOption = $key;
	}

	/**
	 * Builds the HTML of the menu and returns it.
	 *
	 * @param array $setup A hash array of setup keys for the building of the menu, available keys:
	 *  - elementType: The HTML element type to use for the menu, one of the following: "div" or "nav". Defaults to "nav"
	 *  - style: The style of the menu.
	 * @return string The HTML of the menu. An empty string if no options have been configured.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);
		
		if (!is_array($this->options))
			return "";

		if (!isset($setup["elementType"]))
			$setup["elementType"] = "nav";

		$r .= "<".$setup["elementType"].
				" class=\"".
					"UiComponentMenu".
					($this->style ? " ".$this->style : "").
				"\"".
			">";

		// Order the options
		$autoOrder = 0;
		while (list($key, $optionSetup) = each($this->options)) {
			if ($order = $optionSetup["order"]) {
				$autoOrder = $optionSetup["order"];
			}
			else {
				$order = $autoOrder;
				$autoOrder ++;
			}
			$optionSetup["key"] = $key;
			$orderedOptions[$order] = $optionSetup;
		}

		ksort($orderedOptions);

		while (list(, $optionSetup) = each($orderedOptions))
			$r .= $this->buildOptionHtml($optionSetup);

		$r .= "</".$setup["elementType"].">";
		return $r;
	}

	/**
	 * Builds and returns the HTML for the specified option
	 *
	 * @param array $optionSetup The option setup
	 * @return string The HTML
	 */
	function buildOptionHtml($optionSetup, $isSecondLevel = false) {
		if ($optionSetup["href"])
			$r .= "<a href=\"".$optionSetup["href"]."\"";
		else
			$r .= "<div";

		$r .= " class=\"".
				"option".
				($optionSetup["additionalCssClass"] ? " ".$optionSetup["additionalCssClass"] : "").
				($optionSetup["key"] == $this->selectedOption || ($isSecondLevel && $this->selectedSecondLevelOption == $optionSetup["key"]) ? " selected" : "").
			"\"";

		if ($optionSetup["domId"])
			$r .= " id=\"".$optionSetup["domId"]."\"";

		if ($optionSetup["onClick"])
			$r .= " onclick=\"".$optionSetup["onClick"]."\"";

		$r .= ">";

		if ($optionSetup["iconName"])
			$r .= "<div class=\"UiComponentIcon ".$optionSetup["iconName"].($optionSetup["iconVariant"] ? " ".$optionSetup["iconVariant"] : "")."\"></div>";

		if ($optionSetup["title"])
			$r .= "<div class=\"title\">".$optionSetup["title"]."</div>";


		if ($optionSetup["href"])
			$r .= "</a>";
		else
			$r .= "</div>";

		if ($optionSetup["secondLevelOptions"]) {
			$r .= "<div class=\"secondLevelOptions\">";

			// Order the options
			while (list($secondLevelKey, $secondLevelOptionSetup) = each($optionSetup["secondLevelOptions"])) {
				$secondLevelOptionSetup["key"] = $secondLevelKey;
				$secondLevelOrderedOptions[$secondLevelOptionSetup["order"]] = $secondLevelOptionSetup;
			}

			ksort($secondLevelOrderedOptions);

			while (list(, $optionSetup) = each($secondLevelOrderedOptions))
				$r .= $this->buildOptionHtml($optionSetup, true);

			$r .= "</div>";
		}	

		return $r;
	}

}