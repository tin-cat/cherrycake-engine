<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to create a menu option, normally used in conjunction with other higher level UiComponents like UiComponentPanel
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentMenuOptionWithSuboptions extends \Cherrycake\UiComponent {
    protected $dependentCoreModules = [
        "UiComponentJquery",
        "UiComponentMenuOption"
    ];

	function addCssAndJavascript() {
		global $e;
        $e->Css->addFileToSet("coreUiComponents", "UiComponentMenuOptionWithSuboptions.css");
        $e->Javascript->addFileToSet("coreUiComponents", "UiComponentMenuOptionWithSuboptions.js");
    }

    /**
	 * @var boolean $isSelected Whether this component is selected or not
	 */
	private $isSelected = false;
    
    /**
	 * Sets whether this component is selected or not
	 * 
	 * @param boolean $isSelected Whether this component is selected or not
	 */
	function setSelected($isSelected = true) {
		$this->isSelected = $isSelected;
    }
    
    /**
     * @param string $key The key of the sub option to get
     * @return mixed The sub option at the specified key, or false if no suboption with that key.
     */
    function getSubOption($key) {
        return $this->subOptions[$key] ?? false;
    }

	/**
	 * Builds the HTML of the menu option and returns it.
	 *
	 * @param array $setup A hash array of setup keys the building the panel
	 * @return string The HTML.
	 */
	function buildHtml($setup = false) {
        global $e;

        $this->setProperties($setup);
        
        if (!isset($this->domId))
            $this->domId = uniqid();

        // Go through sub options
        $subOptionsHtml = "";
        $isAnySubOptionSelected = false;
        foreach ($this->subOptions as $subOption) {
            if ($subOption->isSelected)
                $isAnySubOptionSelected = true;
            $subOptionsHtml .= $subOption->buildHtml();
        }                

        $r =
            "<div".
                " class=\"".
                    "UiComponentMenuOptionWithSuboptions".
                    ($isAnySubOptionSelected || $this->isSelected ? " open" : null).
                "\"".
                " id=\"".$this->domId."\"".
            ">".
                \Cherrycake\UiComponentMenuOption::build([
                    "title" => $this->title ?? false,
                    "iconName" => $this->iconName ?? false,
                    "iconVariant" => $this->iconVariant ?? false,
                    "isSelected" => $isAnySubOptionSelected ?? false,
                    "isDropdownArrow" => true
                ]).
                "<div class=\"subOptions\">".
                    $subOptionsHtml.
                "</div>".
            "</div>";
        
        $r .= "
            <script>
                $('#".$this->domId."').UiComponentMenuOptionWithSuboptions(".json_encode([
                ]).");
            </script>
        ";

		return $r;
	}
}