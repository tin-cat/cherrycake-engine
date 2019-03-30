<?php

/**
 * UiComponentMenuBar
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to show a Menu Bar
 * 
 * Configuration example for UiComponentMenuBar.config.php:
 * <code>
 * $UiComponentMenuBarConfig = [
 *  "height" => 50, // The height of the menu bar
 * 	"width" => 980, // The width of the contents of the menu bar
 * 	"responsiveBreakpoints" => [
 *      "thresholdWidthToHideLogo" => 980, // The threshold width when the logo will be hidden to make room for options
 * 		"thresholdWidthToUseSlidingPanel" => 500 // The threshold with when a sliding panel will be used to show options instead of showing them on the bar itself
 * 	],
 *  "theme" => "light", // The name of the theme to use
 * 	"iconsVariant" => "black", // The icons variant to use
 *  "logoText" => "Cherrycake", // The default setting for logoText. It is used as the logo image title if a logoImageUrl is specified
 * 	"logoImageUrl" => [
 * 		"light" => "/res/img/logo.svg" // The url of the image logo for the specific theme
 * 	],
 * 	"logoLinkUrl" => "/" // To make the logo a link, set this to the link url
 * ];
 * </code>
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentMenuBar extends UiComponent {
    /**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;
	
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"height" => 50,
		"width" => 980,
		"responsiveBreakpoints" => [
			"thresholdWidthToHideLogo" => 980,
			"thresholdWidthToUseSlidingPanel" => 500
		],
		"theme" => "light",
		"logoText" => false,
		"logoImageUrl" => [
			"light" => "/res/img/logo.svg"
		],
		"logoLinkUrl" => "/"
	];
    
	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery",
		"UiComponentIcons"
    ];
    
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
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentMenuBar.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentMenuBar.js");
    }

    /**
	 * Adds a bunch of options to the menu bar
	 *
	 * @param array $options A hash array with one or more items in the form of $key => $optionSetup, as expected by the addOption method
	 */
	function addOptions($options) {
		while (list($key, $optionSetup) = each($options))
			$this->addOption($key, $optionSetup);
	}

	/**
	 * Adds an option to the menu bar
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
	 * Adds a bunch of options to the menu bar
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
	 * Builds the HTML of the menu bar and returns it.
	 *
	 * @param array $setup A hash array of setup keys for the building of the menu, available keys:
	 *  - style: The style of the menu.
	 * @return string The HTML of the menu. An empty string if no options have been configured.
	 */
	function buildHtml($setup = false) {
		global $e;
		
		$this->treatParameters($setup, [
			"isLogo" => ["default" => true]
		]);

		$this->setProperties($setup);

        $r .=
            "<div".
                " id=\"UiComponentMenuBar\"".
				" class=\"".
                    ($this->getPropertyOrConfig("theme") ? $this->getPropertyOrConfig("theme") : false).
					($this->style ? " ".$this->style : null).
					($this->isLogo ? " withLogo" : null).
				"\"".
			">";
			
		$r .= "<div class=\"wrapper\">";
		
		if ($this->isLogo) {
			$logoImageUrl = $this->getPropertyOrConfig("logoImageUrl")[$this->getPropertyOrConfig("theme")];
			$isLogoText = !$logoImageUrl && $this->getPropertyOrConfig("logoText");
			if ($this->getPropertyOrConfig("logoText") || $logoImageUrl)
				$r .=
					($this->getPropertyOrConfig("logoLinkUrl") ? "<a href=\"".$this->getPropertyOrConfig("logoLinkUrl")."\"" : "<div").
						" class=\"logo ".($isLogoText ? "text" : "image")."\"".
						($logoImageUrl && !$isLogoText ? " title=\"".$this->getPropertyOrConfig("logoText")."\"" : null).
					">".
						($isLogoText ?
							$this->getPropertyOrConfig("logoText")
						: 
							"<img src=\"".$logoImageUrl."\" />"
						).
					($this->getPropertyOrConfig("logoLinkUrl") ? "</a>" : "</div>");
		}

        // Options
        if (is_array($this->options)) {
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

            $r .= "<nav class=\"options\">";
                while (list(, $optionSetup) = each($orderedOptions))
                    $r .= $this->buildOptionHtml($optionSetup);
                reset ($orderedOptions);
            $r .= "</nav>";

            $smallScreenMenuHtml .= "<div class=\"smallScreenMenu\">";
                while (list(, $optionSetup) = each($orderedOptions))
                    $smallScreenMenuHtml .= $this->buildOptionHtml($optionSetup);
                reset ($orderedOptions);
            $smallScreenMenuHtml .= "</div>";
		}
		
		$r .= "</div>";

		$r .= $smallScreenMenuHtml;

        $r .=
            "<div class=\"buttonSmallScreenMenu open UiComponentIcon ".$this->getPropertyOrConfig("iconsVariant")." hamburger\"></div>".
            "<div class=\"buttonSmallScreenMenu close UiComponentIcon ".$this->getPropertyOrConfig("iconsVariant")." close\"></div>";

        $r .= "</div>";
        
        $e->HtmlDocument->addInlineJavascript("$('#UiComponentMenuBar').UiComponentMenuBar();");

		return $r;
	}

	/**
	 * Builds and returns the HTML for the specified option
	 *
	 * @param array $optionSetup The option setup
	 * @return string The HTML
	 */
	function buildOptionHtml($optionSetup, $isSecondLevel = false) {
		if ($optionSetup["href"] && $optionSetup["key"] != $this->selectedOption)
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


		if ($optionSetup["href"] && $optionSetup["key"] != $this->selectedOption)
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