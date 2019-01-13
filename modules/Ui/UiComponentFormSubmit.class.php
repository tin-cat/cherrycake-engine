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
	 * @var array $dependentCherrycakeUiComponents Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCherrycakeUiComponents = [
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
	 * Builds an object and returns it
	 * 
	 * @param array $setup A hash array with the select specs
	 * @return UiComponentFormInput The object
	 */
	static function build($setup = false) {
		$UiComponentFormSubmit = new UiComponentFormSubmit($setup);
		return $UiComponentFormSubmit;
	}

	/**
	 * Setup keys:
	 *
	 * * style: The additional styles, separated with spaces
	 * * additionalCssClasses: The additional css Class name(s)
	 * * domId: The Dom id for the UiComponentFormInput element
	 * * title: The text of the submit button
	 * * iconName: The name of the icon, if any
	 * * iconVariant: The variant of the icon
	 * * iconPosition: (left/right) The position of the icon. Default: left
	 * * isDisabled: Whether the input is disabled or not. Defaults to false
	 * * isAutoFocus: Whether the input must be automatically focused on page load
	 * * onClick: The onclick Javascript.
	 *
	 * @param array $setup A hash array with the setup keys
	 */
	function __construct($setup = false) {
		if (is_array($setup))
			while (list($key, $value) = each($setup))
				$this->$key = $value;
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
			]);
	}
}