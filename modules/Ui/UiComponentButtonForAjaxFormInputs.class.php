<?php

/**
 * UiComponentButtonForAjaxFormInputs
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentButtonForAjaxFormInputs
 *
 * A UiComponentButton made to interact with other UiComponentFormInputAjax or UiComponentFormTextAjax components, so when this button is clicked, all of them are saved, and an action is taken if all saves were ok.
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentButtonForAjaxFormInputs extends UiComponentButton {
    /**
     * @var array $ajaxInputsToSaveDomIds An array of strings specifying the Dom ids of all the UiComponentFormInputAjax or similar elements.
     */
    protected $ajaxInputsToSaveDomIds;

    /**
     * @var string $onAllSucceedAjaxUrl The ajax URL to call if all inputs have succeeded on saving. Leave to false if no ajax request should be done in that case.
     */
    protected $onAllSucceedAjaxUrl;

     /**
     * @var string $onSomeErrorsAjaxUrl The ajax URL to call if some inputs have errors on saving. Leave to false if no ajax request should be done in that case.
     */
    protected $onSomeErrorsAjaxUrl;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentButton"
    ];
    
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentButtonForAjaxFormInputs.js");
	}

	function buildHtml($setup = false) {
        $this->setProperties($setup);
        
        foreach ($this->ajaxInputsToSaveDomIds as $domId)
            $jQuerySelector .= "#".$domId.",";
        $jQuerySelector = substr($jQuerySelector, 0, -1);

        $setup["onClick"] = "function(button) {
            UiComponentMultipleFormInputAjaxSaveAjax({
                button: $(button),
                elements: $('".$jQuerySelector."'),
                ajaxQueryUrlOnAllSucceed: '".$this->onAllSucceedAjaxUrl."',
                ajaxQueryUrlOnSomeErrors: '".$this->onSomeErrorsAjaxUrl."'
            });
        }";

        return parent::buildHtml($setup);
	}
}