<?php

/**
 * UiComponentFormRadios
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form radios
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormRadios extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $name;
	protected $title;
	protected $value;

	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormRadios.css");
	}
	
	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		global $e;
		$this->setProperties($setup);

		if ($this->error) {
			if (!$this->domId)
				$this->domId = uniqid();

			$e->loadCoreModule("HtmlDocument");

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
					"UiComponentFormRadios".
					($this->style ? " ".$this->style : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
			">".
			($this->title ? "<div class=\"title\">".$this->title."</div>" : null);

		while (list($key, $data) = each($this->items)) {
			if (!is_array($data))
				$data = ["title" => $data];
			
			$html .= $e->Ui->getUiComponent("UiComponentFormRadio")->buildHtml([
				"name" => $this->name,
				"value" => $key,
				"title" => $data["title"],
				"subTitle" => $data["subTitle"],
				"isChecked" => $key == $this->value
			]);
		}
		$html .=
			"</div>";

		return $html;
	}
}