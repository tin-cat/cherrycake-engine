<?php

/**
 * UiComponentFormCheckboxes
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form checkboxes
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormCheckboxes extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $title;

	/**
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
		"UiComponentTooltip"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormCheckboxes.css");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if ($this->error) {
			global $e;

			if (!$this->domId)
				$this->domId = uniqid();

			$e->loadCherrycakeModule("HtmlDocument");

			$e->HtmlDocument->addInlineJavascript("
				$('#".$this->domId."').UiComponentTooltip({
					isOpenOnInit: true,
					isCloseWhenOthersOpen: false,
					style: 'styleSimple styleWarning',
					content: ".json_encode(
						UiComponentTooltip::buildContentItem(
							UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE,
							[
								"title" => $this->error
							]
						)
					).",
					position: 'rightTop',
					isTapToPopupOnSmallScreens: true
				});
			");
		}
		
		$html =
			"<div ".
				"class=\"".
					"UiComponentFormCheckboxes".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
			($this->title ? "<div class=\"title\">".$this->title."</div>" : null);

		while (list($key, $item) = each($this->items))
			$html .=
				"<div class=\"item\">".
					"<input ".
						"type=\"checkbox\" ".
						"name=\"".
							(!$item["name"] && $key && $this->name ? $this->name."[".$key."]" : $item["name"]).
						"\" ".
						($item["value"] ? "value=\"".htmlspecialchars($item["value"])."\" " : null).
						($item["isDisabled"] ? "disabled " : null).
						($item["isAutoFocus"] ? "autofocus " : null).
						($item["onChange"] ? "onchange=\"".$item["onChange"]."\" " : null).
						($item["isChecked"] ? "checked " : null).
					"/>".
					($item["description"] ? "<div class=\"description\">".$item["description"]."</div>" : null).
				"</div>";
		$html .=
			"</div>";

		return $html;
	}
}