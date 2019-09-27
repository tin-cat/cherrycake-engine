<?php

/**
 * UiComponentPanel
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to build a complex panel aimed to compose the entire webpage structure. The panel is comprised of three sections:
 * 
 * . "Top" Is the top horizontal menu bar, where blocks are added as a horizontal list. It acts as a regular top menu bar that usually contains the main options of the site, the icons for notifications, and the login/signup options. It can hold Block objects of the following classes:
 *      . UiComponentBlockOption

 * . "Main" Is the left vertical bar that is open by default and can slide in and out by clicking the hamburger icon. It's aimed to hold subsections of a given main section, a navigation tree, or other forms of blocks. It can hold Block objects of the following classes:
 *      . UiComponentMenuOption
 *      . UiComponentLabel
 * 
 * . "Opt" Is the right vertical bar that is closed by default and can slide in and out, and is open and closed by clicking the configuration icon on the "Main" section. This section is not yet implemented and cannot be used currently.
 * 
 * There are also two other sectoins of the panel that are not configurable with blocks:
 * 
 * . The header, which is where the logo is placed. This is configured via the "logo" configuration parameter.
 * . The content section, which is where the main content of each page is shown. This is set up by passing a "content" to the buildHtml method.
 * 
 * Configuration example for UiComponentPanel.config.php:
 * <code>
 * $UiComponentPanelConfig = [
 *  "topHeight" => 50, // The height of the top bar
 *  "mainWidth" => 230, // The width of the left main panel when open
 *  "mainSmallWidth" => 50, // The width of the left main panel when the screen is small
 * 	"responsiveBreakpoints" => [
 *      "smallScreenWidthThreshold" => 500 // The threshold width when the panel will collapse to fit small screens
 * 	],
 *  "theme" => false, // The default theme. Set it to false to not apply any specific theme
 *  "logo" => [ // Sets up the logo
 *      "fullImageUrl" => "/res/img/fullLogo.svg", // The image to show as logo when the panel is working in a big screen
 *      "smallImageUrl" => "/res/img/smallLogo.svg" // The image to show as logo when the panel is working in a small screen
 *      "linkRequest" => false // If set, the logo will link to this request
 *  ],
 *  "transitionTime" => 0.25, // The time it takes for css transitions when the panel changes shape
 *  "isMainOpen" => true // Whether the main section should be open by default (except if we're in a small screen)
 *  "isAllMainOptionsOpen" => false // Whether all sections should be open by default or not
 * ];
 * </code>
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentPanel extends UiComponent {
    /**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;
	
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
        "topHeight" => 55,
        "mainWidth" => 230,
        "mainSmallWidth" => 60,
		"responsiveBreakpoints" => [
			"smallScreenWidthThreshold" => 600
		],
		"theme" => false,
		"logo" => [
            "fullImageUrl" => "/res/img/fullLogo.svg",
            "smallImageUrl" => "/res/img/smallLogo.svg"
        ],
        "transitionTime" => 0.25, // The time it takes for css transitions when the panel changes shape
        "isMainOpen" => true, // Whether the main section should be open by default (except if we're in a small screen)
        "isAllMainOptionsOpen" => false, // Whether all sections should be open by default or not
        "iconHamburgerName" => "hamburger",
        "iconHamburgerVariant" => "black"
	];
    
	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
        "UiComponentJquery",
        "UiComponentIcons",
        "UiComponentMenuOption",
        "UiComponentMenuOptionWithSuboptions"
    ];
    
    /**
	 * @var array $blocks The blocks inside each section of the panel
	 */
    protected $blocks;

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentPanel.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentPanel.js");
    }

    /**
	 * Adds a bunch of blocks to the given section
	 *
     * @param string $section The name of the section to add the block to
	 * @param array $blocks A hash array with one or more Block objects in the form of $key => $block
	 */
	function addBlocks($section, $blocks) {
		while (list($key, $block) = each($blocks))
			$this->addBlock($section, $key, $block);
	}

	/**
	 * Adds a block to the specified section
	 *
     * @param string $section The name of the section to add the block to
	 * @param string $key The key of the block, for further reference.
	 * @param UiComponent $block A UiComponent* object
	 */
	function addBlock($section, $key, $block) {
		$this->blocks[$section][$key] = $block;
    }

    /**
     * @param string $section The name of the section
	 * @param string $key The key of the block
     * @return UiComponent The UiComponent* object with the given key, in the specified section
     */
    function getBlock($section, $key) {
        return $this->getBlocks($section)[$key];
    }

    /**
     * @param string $section The name of the section
     * @return array The UiComponent* objects in the specified section
     */
    function getBlocks($section) {
        return $this->blocks[$section];
    }

    /**
     * @param string $section The name of the section
     * @return boolean Whether the specified section has blocks or not
     */
    function isBlocks($section) {
        return is_array($this->blocks[$section]);
    }
    
    /**
	 * Builds the HTML of the panel and returns it.
	 *
	 * @param array $setup A hash array of setup keys for building the panel, available keys:
     * * theme: The theme to use, overriding the configured one
     * * isAllMainOptionsOpen: Whether to show all main options open or not. Only the selected one will be open.
     * * mainOptionSelected: The name of the selected option in the main block
     * * mainSubOptionSelected: The name of the selected option in the main blcok 
	 * @return string The HTML
	 */
	function buildHtml($setup = false) {
        global $e;

		$this->setProperties($setup);

        $r .=
            "<div".
                " id=\"UiComponentPanel\"".
				" class=\"".
                    ($this->getPropertyOrConfig("theme") ? $this->getPropertyOrConfig("theme") : null).
                    ($this->getPropertyOrConfig("style") ? " ".$this->getPropertyOrConfig("style") : null).
                    " noAnimations".
				"\"".
            ">";

        // Header
        $r .=
            "<div".
                " class=\"header\"".
            ">".

                ($this->getPropertyOrConfig("logo")["linkRequest"] ? "<a href=\"".$e->Actions->getAction($this->getPropertyOrConfig("logo")["linkRequest"])->request->buildUrl()."\"" : "<div").
                    " class=\"logo wide\"".
                    " style=\"".
                        "background-image: url('".$this->getPropertyOrConfig("logo")["fullImageUrl"]."');".
                    "\"".
                ">".
                ($this->getPropertyOrConfig("logo")["linkRequest"] ? "</a>" : "</div>").

                ($this->getPropertyOrConfig("logo")["linkRequest"] ? "<a href=\"".$e->Actions->getAction($this->getPropertyOrConfig("logo")["linkRequest"])->request->buildUrl()."\"" : "<div").
                    " class=\"logo small\"".
                    " style=\"".
                        "background-image: url('".$this->getPropertyOrConfig("logo")["smallImageUrl"]."');".
                    "\"".
                ">".
                ($this->getPropertyOrConfig("logo")["linkRequest"] ? "</a>" : "</div>").

            "</div>";

         $r .=
            "<div class=\"top\">".
                $this->buildHtmlSection("topLeft").
                $this->buildHtmlSection("topRight").
            "</div>".
            $this->buildHtmlSection("main", [
                "isAllSelected" => $this->isAllMainOptionsOpen,
                "optionSelected" => $this->mainOptionSelected,
                "subOptionSelected" => $this->mainSubOptionSelected
            ]).
            "<div class=\"content\">".
                $setup["content"].
            "</div>";
        
        $r .= "</div>";
        
        $e->HtmlDocument->addInlineJavascript("$('#UiComponentPanel').UiComponentPanel(".json_encode([
            "isMainOpen" => $this->getPropertyOrConfig("isMainOpen"),
            "iconHamburgerName" => $this->getConfig("iconHamburgerName"),
            "iconHamburgerVariant" => $this->getConfig("iconHamburgerVariant")
        ]).");");

		return $r;
    }

    /**
     * @param string $section The name of the section for which to get the HTML for.
     * @param array $setup Optional array of setup options with the following possible keys:
     * * isOpen: Whether this section should be open by default
     * * isAllSelected: Whether to show all the options selected instead of only the specific optionSelected
     * * optionSelected: The name of the option that has to be selected on this section, if applicable
     * * subOptionSelected: The name of the sub option inside the optionSelected that has to be selected on this section, if applicable
     * @return string The HTML for the given section of the panel.
     */
    private function buildHtmlSection($section, $setup = false) {
        $r .= "<div class=\"$section\">";
        if ($this->isBlocks($section)) {
            $blocks = $this->getBlocks($section);
            while (list($blockName, $uiComponent) = each($blocks)) {

                // If it's a UiComponentButton, set it to transparent
                if (get_class($uiComponent) == "Cherrycake\UiComponentButton")
                    $uiComponent->isTransparent = true;
                
                switch (get_class($uiComponent)) {

                    case "Cherrycake\UiComponentMenuOption":
                        $uiComponent->setSelected($setup["optionSelected"] == $blockName || $setup["isAllSelected"]);
                        break;

                    case "Cherrycake\UiComponentMenuOptionWithSuboptions":
                        if ($setup["optionSelected"] == $blockName || $setup["isAllSelected"]) {
                            $uiComponent->setSelected(true);
                            if ($setup["subOptionSelected"] && $subOption = $uiComponent->getSubOption($setup["subOptionSelected"])) {
                                $subOption->setSelected(true);
                            }
                        }
                        break;

                }

                $r .= $uiComponent->buildHtml();
            }
        }
        $r .= "</div>";
        return $r;
    }
    
    /**
     * Since UiComponentPanel is built to include the HTML to be shown on screen in one of its own panels, for ease of use it is capable of setting an entire HTML response just by calling this method, which takes the same $setup configuration as the buildHtml method.
     * For ease of use, call this method in your code to dump all the necessary html code to build a UiComponentPanel, instead of parsing an HTML pattern calling buildHtml.
     * Pass the "content" setup key with the HTML of the page.
     * 
     * @param array $setup A hash array of setup keys for the building of the panel, same keys as in the buildHtml method.
     */
    function setOutputResponse($setup = false) {
        global $e;
        $e->Output->setResponse(new \Cherrycake\ResponseTextHtml([
			"code" => $code,
            "payload" =>
                $e->HtmlDocument->header($setup["htmlDocumentHeaderSetup"]).
                $this->buildHtml($setup).
                $e->HtmlDocument->footer()
		]));
    }
}