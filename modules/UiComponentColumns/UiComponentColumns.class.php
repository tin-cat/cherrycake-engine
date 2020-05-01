<?php

/**
 * UiComponentColumns
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * A Ui component to build a structure of columns
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentColumns extends \Cherrycake\UiComponent {
	protected $isWrap = false;
    protected $isInnerGap = true;
	protected $domId;
	protected $additionalCssClasses;
	protected $style;

	function addCssAndJavascript() {
		global $e;
		$e->Css->addFileToSet("coreUiComponents", "UiComponentColumns.css");
		return true;
    }
    
    /**
	 * Builds the HTML of the column structure and returns it.
	 *
	 * @param array $setup A hash array of setup keys for building the panel, available keys:
     * * style: The style name of the UiComponentColumns, or an array of style names
	 * * columns: An ordered array of columns, where each item has the following available keys;
	 * * * html: The content
	 * * * style: The style name of the column, or an array of style names
	 * @return string The HTML
	 */
	function buildHtml($setup = false) {
        global $e;

		$this->setProperties($setup);

        $r =
            "<div".
				" class=\"".
					"UiComponentColumns".
                    ($this->style ? " ".(is_array($this->style) ? implode(" ", $this->style) : $this->style) : null).
					($this->additionalCssClasses ? " ".$this->additionalCssClasses : null).
					($this->isWrap ? " wrap" : null).
					($this->isInnerGap ? " innerGap" : null).
				"\"".
				($this->domId ? " id=\"".$this->domId."\"" : null).
            ">";
		
		foreach ($this->columns as $column) {
			$r .=
				"<div".
					($column["style"] ?? false ?
						" class=\"".
							(is_array($column["style"]) ? implode(" ", $column["style"]) : $column["style"]).
						"\""
					: null).
				">".
					$column["html"].
				"</div>";
		}
		reset($this->columns);

		$r .= "</div>";

		return $r;
    }
}