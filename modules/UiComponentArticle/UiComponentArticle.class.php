<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * A Ui component that includes a css library to build beautiful articles, posts or contents
 * 
 * Configuration example for UiComponentgrid.config.php:
 * <code>
 * $UiComponentArticleConfig = [
 *  "baseGap" => "40", // The base number of pixels to consider as a gap between elements
 *  "responsiveBreakpoints" => [
 *      "medium" => 980, // When the screen is narrower than this width, the article will be adapted for medium sized screens
 *      "small" => 500 // When the screen is narrower than this width, the article will be adapted for small sized screens
 *  ]
 * ];
 * </code>
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentArticle extends \Cherrycake\UiComponent {
    protected $isConfigFile = true;
    
	var $config = [
        "baseGap" => 10,
		"responsiveBreakpoints" => [
			"medium" => 980,
			"small" => 600
		]
    ];
    
    protected $dependentCoreModules = [
		"UiComponentMenuBar"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentArticle.css");
		return true;
	}
}