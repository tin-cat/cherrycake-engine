<?php

/**
 * UiComponentFormSubmit
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form submit buttons
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormSubmit extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $isDisabled = false;
	protected $title;
	protected $iconName;
	protected $iconVariant;
	protected $iconPosition;
	protected $isAutoFocus;
	public $onClick;
	public $isAddJsControl;

	/**
	 * @var array $dependentCoreUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreUiComponents = [
		"UiComponentButton",
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
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormSubmit.css");
	}

	/**
	 * Sets the onClick for this submit button
	 * @param string $onClick The onClick javascript
	 */
	function setOnClick($onClick) {
		$this->onClick = $onClick;
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
		
		global $e;
		return
			$e->Ui->uiComponents["UiComponentButton"]->build([
				"additionalCssClasses" => $this->additionalCssClasses." UiComponentFormSubmit",
				"style" => $this->style,
				"domId" => $this->domId,
				"title" => $this->title,
				"iconVariant" => $this->iconVariant,
				"iconName" => $this->iconName,
				"iconPosition" => $this->iconPosition,
				"onClick" => $this->onClick,
				"isAddJsControl" => $this->isAddJsControl
			])->buildHtml();
	}
}