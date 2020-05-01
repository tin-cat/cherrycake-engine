<?php

/**
 * UiComponentForm
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * UiComponentForm
 *
 * A Ui component to create forms
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentForm extends \Cherrycake\UiComponent {
	protected $domId;
	protected $style;
	protected $additionalCssClasses;
	protected $title;
	protected $request;
	protected $method = "post";
	protected $url;
	protected $items;

	/**
	 * @var array $dependentCoreModules Cherrycake UiComponent names that are required by this module
	 */
	protected $dependentCoreModules = [
		"UiComponentColumns",
		"UiComponentFormInput",
		"UiComponentFormUneditable"
	];

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentForm.css");
		$e->Javascript->addFileToSet("coreUiComponents", "UiComponentForm.js");
		return true;
	}

	/**
	 * Builds a complex form with the given items.
	 *
	 * Setup keys:
	 *
	 * * style: The additional style for the form
	 * * request: An optional Request object. If specified, the created form will be submitted according to the Request, and will take into account any security additional measures that need to be taken when creating forms that lead to requests, like CSRF attacks mitigation.
	 * * method: <post|get> Default: post (Optional. If not specified, a regular div will be used instead of a form)
	 * * url: The URL to post this form to (Optional. If not specified, a regular div will be used instead of a form)
	 * * domId: The Dom id for the form element
	 * * items: An array of: UiComponentForm* objects or arrays of UiComponentForm* objects if you want to visually group them in a UiComponentGrid. If a request is specified, each UiComponentForm*'s name must ideally match one of the request parameters.
	 * * title: A title for the form
	 *
	 * @param array $setup A hash array with the specs
	 * @return string The Html
	 */
	function buildHtml($setup = false) {
		$this->setProperties($setup);

		if (!$this->domId)
			$this->domId = uniqid();

		global $e;

		// If a Request has been specified, set the proper url and parameters
		if ($this->request)
			$this->url = $this->request->buildUrl(["isIncludeUrlParameters" => false]);

		$htmlContent = "";
		
		if ($this->title)
			$htmlContent .= "<div class=\"title\">".$this->title."</div>";

		foreach ($this->items as $UiComponentFormItem)
			$htmlContent .= $this->buildHtmlForUiComponentFormItem($UiComponentFormItem);
		
		if ($this->url && $this->method)
			$r = "<form ".($this->domId ? " id=\"".$this->domId."\"" : null).">".$htmlContent."</form>";
		else
			$r = "<div ".($this->domId ? " id=\"".$this->domId."\"" : null).">".$htmlContent."</div>";

		$r .= "
			<script>
				$('#".$this->domId."').UiComponentForm(".json_encode([
					"style" => $this->style,
					"additionalCssClasses" => $this->additionalCssClasses,
					"method" => $this->method,
					"url" => $this->url
				]).");
			</script>
		";

		return $r;
	}

	/**
	 * Build the HTML code of the specified UiComponentformItem for the form
	 * @param UiComponentFormItem $UiComponentFormItem One of the available UiComponentFormItem* object
	 * @return string The HTML
	 */
	function buildHtmlForUiComponentFormItem($UiComponentFormItem) {
		if (is_array($UiComponentFormItem)) {
			global $e;
			return $e->UiComponentColumns->buildHtml([
				"isWrap" => true,
				"columns" => call_user_func(function() use ($UiComponentFormItem) {
					foreach ($UiComponentFormItem as $subUiComponentFormItem)
						$r[] = [
							"html" => $this->buildHtmlForUiComponentFormItem($subUiComponentFormItem)
						];
					return $r;
				})
			]);
		}

		if (strstr(get_class($UiComponentFormItem), "UiComponentFormSubmit"))
			$UiComponentFormItem->onClick = "function() { $('#".$this->domId."').UiComponentForm('submit'); }";
		
		return $UiComponentFormItem;
	}
}