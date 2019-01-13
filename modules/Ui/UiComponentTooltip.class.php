<?php

/**
 * UiComponentTooltip
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SEPARATOR = 0;
const UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_CONTENT = 1;
const UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE = 2;
const UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_OPTION = 3;

/**
 * UiComponentTooltip
 *
 * A Ui component to show tooltips
 *
 * Configuration example for UiComponentjquery.config.php:
 * <code>
 *  $UiComponentAjaxConfig = [
 *      "defaultPosition" => "bottomLeft", // The default position of the tooltip if no other assigned: [bottomLeft | bottomRight | topLeft | topRight | leftCenter | rightCenter | topCenter | bottomCenter]. Defaults to "bottomLeft"
 *      "defaultIsOpenDelay" => false, // Default whether to wait defaultOpenDelay milliseconds before opening the tooltip.
 *      "defaultOpenDelay" => 300, // The default milliseconds the tooltip waits until it appears
 *      "defaultIsCloseDelay" => false, // Default whether to wait defaultCloseDelay milliseconds before closing the tooltip when it has to be closed
 *      "defaultCloseDelay" => 300, // The default milliseconds the tooltip waits until it closes
 *      "arrowSize" => 15, // The size of the arrow in pixels
 *      "arrowMargin" => 15, // The margin of the arrow against the borders of the tooltip in pixels
 *      "margin" => 3 // The margin to keep around the object triggering the tooltip
 *  ];
 * </code>
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTooltip extends UiComponent
{
	/**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Holds the default configuration for this UiComponent
	 */
	protected $config = [
		"defaultPosition" => "bottomLeft",
		"defaultIsOpenDelay" => false,
		"defaultOpenDelay" => 50,
		"defaultIsCloseDelay" => true,
		"defaultCloseDelay" => 150,
		"arrowSize" => 15,
		"arrowMargin" => 13,
		"margin" => 3
	];

	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentJquery",
		"UiComponentNotice"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentTooltip.css");
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentTooltip.js");
	}

	/**
	 * buildContent
	 *
	 * Builds a proper HTML content string for the tooltip based on the different items given in $contents
	 *
	 * @param array $contents An array of contents where each item has the following syntax. If a string is given, just the string is returned
	 *  <item name> = [
	 *      "order" => <The order of the option in relation to other options>
	 *      "type" => <One of the available UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_* consts>
	 *      "setup" => [
	 *          // For UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE:
	 *          "title" => <The title of the option>
	 *          "iconName" => <The optional icon name of the option>
	 *          "iconVariant" => <The optional icon variant of the option>
	 *          "onHoverIconVariant" => <The icon variant to apply on hovering the item, overrides iconVariant>
	 *          "nonHoverIconVariant" => <The icon variant to apply when not hovering the item, overrides iconVariant>
	 *          "href" => <The URL where this option should link on click>
	 *          "onClick" => <Javascript code to execute on click, overrides "href">
	 *
	 *          // Common for all types
	 *          "style" => <Any optional additional css styles>
	 *      ]
	 *  ]
	 *
	 * @return string The proper HTML contents for the tooltip
	 */
	static function buildContent($contents) {
		if (!is_array($contents))
			return $contents;

		// Order the options
		while (list($name, $setup) = each($contents)) {
			$setup["name"] = $name;
			$contentsOrdered[$setup["order"]] = $setup;
		}

		ksort($contentsOrdered);

		foreach($contentsOrdered as $item)
			$r .= UiComponentTooltip::buildContentItem($item["type"], $item["setup"]);

		return $r;
	}

	/**
	 * buildContentItem
	 *
	 * Intended to be used by the buildContent method, builds the HTML for a single item for the tooltip's contents
	 *
	 * @param integer $type One of the available UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_* consts
	 * @param $setup The setup options for the item, as specified in buildContent
	 * @return string The HTML for the given item
	 */
	static function buildContentItem($type, $setup) {
		switch ($type) {
			case UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SEPARATOR:
				$r .= "<hr".($setup["additionalCssClass"] ? " class=\"".$setup["additionalCssClass"]."\"" : "").">";
				break;

			case UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_CONTENT:
				$r .=
					"<div".
						" class=\"".
							"content".
							($setup["additionalCssClass"] ? " ".$setup["additionalCssClass"] : "").
						"\"".
					">".
						$setup["content"].
					"</div>";
				break;

			case UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE:
				$r .=
					"<div".
						" class=\"".
							"simple".
							($setup["additionalCssClass"] ? " ".$setup["additionalCssClass"] : "").
						"\"".
					">".
						($setup["iconName"] ? "<div class=\"UiComponentIcon ".$setup["iconName"].($setup["iconVariant"] ? " ".$setup["iconVariant"] : "")."\"></div>" : "").
						($setup["title"] ? "<div class=\"title\">".$setup["title"]."</div>" : "").
					"</div>";
				break;

			case UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_OPTION:
				if ($setup["nonHoverIconVariant"])
					$setup["iconVariant"] = $setup["nonHoverIconVariant"];

				$r .=
					"<a".
						" class=\"".
							"option".
						"\"".
						($setup["href"] && !$setup["onClick"] ? " href=\"".$setup["href"]."\"" : "").
						($setup["onClick"] ? " onclick=\"".$setup["onClick"]."\"" : "").
						($setup["onHoverIconVariant"] ? " onmouseenter=\"$('> .UiComponentIcon', this).removeClass('".$setup["nonHoverIconVariant"]."').addClass('".$setup["onHoverIconVariant"]."');\"" : "").
						($setup["nonHoverIconVariant"] ? " onmouseleave=\"$('> .UiComponentIcon', this).removeClass('".$setup["onHoverIconVariant"]."').addClass('".$setup["nonHoverIconVariant"]."');\"" : "").
					">".
						($setup["iconName"] ? "<div class=\"UiComponentIcon ".$setup["iconName"].($setup["iconVariant"] ? " ".$setup["iconVariant"] : "")."\"></div>" : "").
						"<div class=\"title\">".$setup["title"]."</div>".
					"</a>";
				break;
		}

		return $r;
	}
}