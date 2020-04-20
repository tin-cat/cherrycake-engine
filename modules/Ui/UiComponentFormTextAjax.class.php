<?php

/**
 * UiComponentFormTextAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component for form textareas that send the data independently via Ajax
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormTextAjax extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $value;
	protected $height;
	protected $maxLength = 65535;
	protected $placeHolder;
	protected $isDisabled = false;
	protected $isAutoComplete = true;
	protected $isAutocapitalize = false;
	protected $isAutocorrect = false;
	protected $isSpellCheck = false;
	protected $onChange;
	protected $title;

	protected $buttonTitle;
	protected $buttonIconName = "ok";

	protected $saveAjaxUrl;
	protected $saveAjaxKey;

	protected $dependentCoreUiComponents = [
        "UiComponentFormText",
        "UiComponentFormInputAjax"
	];

	protected $dependentCoreModules = [
		"HtmlDocument",
		"Security"
	];

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentFormTextAjax.css");
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		global $e;

		$this->setProperties($setup);

		if (!$this->domId)
			$this->domId = uniqid();

		$r =
            "<div ".
                "class=\"".
                    "UiComponentFormText ajax".
                    ($this->style ? " ".$this->style : null).
                    ($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
                "\"".
                ($this->domId ? " id=\"".$this->domId."\"" : null).
            ">".
            	($this->title ? "<div class=\"title\">".$this->title."</div>" : null).

				"<div class=\"wrapper\">".

                "<textarea".
                    ($this->maxLength ? " maxlength=\"".$this->maxLength."\" " : null).
                    ($this->placeHolder ? " placeholder=\"".htmlspecialchars($this->placeHolder)."\" " : null).
                    ($this->isDisabled ? " disabled " : null).
                    ($this->isAutoFocus ? " autofocus " : null).
                    " autocomplete=\"".($this->isAutoComplete ? "on" : "off")."\" ".
					" autocapitalize=\"".($this->isAutocapitalize ? "on" : "off")."\" ".
					" autocorrect=\"".($this->isAutocorrect ? "on" : "off")."\" ".
                    " spellcheck=\"".($this->isSpellCheck ? "true" : "false")."\" ".
                    ($this->onChange ? " onchange=\"".$this->onChange."\" " : null).
                    ($this->height ? " style=\"height: ".$this->height."px;\" " : null).
                ">".
                    ($this->value ? htmlspecialchars($this->value) : null).
                "</textarea>".

				UiComponentButton::build([
					"additionalCssClasses" => "send",
					"title" => $this->buttonTitle,
					"iconName" => $this->buttonIconName
				]).

				"</div>".

            "</div>";

		$e->HtmlDocument->addInlineJavascript("
			$('#".$this->domId."').UiComponentFormInputAjax({
                inputElementType: 'textarea',
				saveAjaxUrl: '".$this->saveAjaxUrl."',
				saveAjaxKey: '".$this->saveAjaxKey."'
			});
		");

		return $r;
	}
}