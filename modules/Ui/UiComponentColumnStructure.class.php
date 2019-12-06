<?php

/**
 * UiComponentColumnStructure
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to build a structure of columns
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentColumnStructure extends UiComponent {
    /**
	 * @var bool $isConfig Sets whether this UiComponent has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = false;

	/**
	 * AddCssAndJavascriptSetsToHtmlDocument
	 *
	 * Adds the Css and Javascript sets that are required to load by HtmlDocument module for this UI component to properly work
	 */
	function addCssAndJavascript() {
		parent::addCssAndJavascript();
		global $e;
		$e->Css->addFileToSet($this->getConfig("cssSetName"), "UiComponentColumnStructure.css");
    }
    
    /**
	 * Builds the HTML of the column structure and returns it.
	 *
	 * @param array $setup A hash array of setup keys for building the panel, available keys:
     * * style: The style name of the UiComponentColumnStructure, or an array of style names
	 * * columns: An ordered array of columns, where each item has the following available keys;
	 * * * html: The content
	 * * * style: The style name of the column, or an array of style names
	 * @return string The HTML
	 */
	function buildHtml($setup = false) {
        global $e;

		$this->setProperties($setup);

        $r .=
            "<div".
				" class=\"".
					"UiComponentColumnStructure".
                    ($this->style ? " ".(is_array($this->style) ? implode(" ", $this->style) : $this->style) : null).
					($this->isWrap ? " wrap" : null).
				"\"".
            ">";
		
		foreach ($this->columns as $column) {
			$r .=
				"<div".
					($column["style"] ?
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