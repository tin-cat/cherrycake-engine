<?php

/**
 * UiComponentFormInput
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form inputs
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormInput extends \Cherrycake\UiComponent {
	protected $domId;
	protected $type = "text";
	protected $style;
	protected $name;
	protected $value;
	protected $size;
	protected $maxLength = 255;
	protected $placeHolder;
	protected $isDisabled = false;
	protected $isAutoFocus = false;
	protected $isAutoComplete = true;
	protected $isAutocapitalize = false;
	protected $isAutocorrect = false;
	protected $isSpellCheck = false;
	protected $onChange;
	protected $title;
	protected $isSubmitOnEnter;
	protected $error;

	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentTooltip"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentFormInput.css");
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentFormInput.js");
		return true;
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if (!$this->domId)
			$this->domId = uniqid();

		$r = "<div id=\"".$this->domId."\"></div>";
		$r .= "
			<script>
				$('#".$this->domId."').UiComponentFormInput(".json_encode([
					"type" => $this->type,
					"style" => $this->style,
					"name" => $this->name,
					"value" => $this->value,
					"size" => $this->size,
					"maxLength" => $this->maxLength,
					"placeHolder" => $this->placeHolder,
					"isDisabled" => $this->isDisabled,
					"isAutoComplete" => $this->isAutoComplete,
					"isAutoFocus" => $this->isAutoFocus,
					"isAutocapitalize" => $this->isAutocapitalize,
					"isAutocorrect" => $this->isAutocorrect,
					"isSpellCheck" => $this->isSpellCheck,
					"onChange" => $this->onChange,
					"title" => $this->title,
					"isSubmitOnEnter" => $this->isSubmitOnEnter
				]).");
			</script>
		";

		if ($this->error) {
			global $e;

			if (!$this->domId)
				$this->domId = uniqid();

			$e->loadCoreModule("HtmlDocument");

			$e->HtmlDocument->addInlineJavascript("
				$('#".$this->domId."').UiComponentTooltip({
					isOpenOnInit: true,
					isCloseWhenOthersOpen: false,
					style: 'styleWarning',
					content: ".json_encode(
						UiComponentTooltip::buildContentItem(
							UICOMPONENTTOOLTIP_CONTENT_ITEM_TYPE_SIMPLE,
							[
								"title" => is_array($this->error) ? implode($this->error, "<br>") : $this->error
							]
						)
					).",
					position: 'rightCenter',
					isTapToPopupOnSmallScreens: true
				});
			");
		}

		return $r;
	}
}
