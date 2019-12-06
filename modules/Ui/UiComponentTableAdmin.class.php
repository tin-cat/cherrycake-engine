<?php

/**
 * UiComponentTableAdmin
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to admin database tables. Works in conjunction with the TableAdmin module.
 * @package Cherrycake
 * @category Classes
 */
class UiComponentTableAdmin extends UiComponent {
	protected $dependentCherrycakeUiComponents = [
        "UiComponentAjax",
        "UiComponentTable",
        "UiComponentButton",
		"UiComponentIcons"
    ];

	function addCssAndJavascript() {
		global $e;
		$e->Javascript->addFileToSet($this->getConfig("javascriptSetName"), "UiComponentTableAdmin.js");
    }
    
    /**
     * @param string $mapName The name of the TableAdmin map to use. Must've been defined previously by calling TableAdmin::map
	 * @param array $setup A hash array of setup keys for the building of the table, available keys:
     * * title: Optional title for the table.
	 * * style: The style of the table.
     * * additionalCssClasses: Additional CSS classes for the table admin.
	 * @return mixed The HTML of the table admin, or false if the specified map doesn't exists.
	 */
	function buildHtml($mapName, $setup = false) {
        global $e;

        if (!$map = $e->TableAdmin->getMap($mapName))
            return false;
        
        if (!is_array($map["columns"]))
            return false;
		
		$this->treatParameters($setup, [
            "domId" => ["default" => uniqid()],
            "style" => ["default" => $map["style"]],
            "additionalCssClasses" => ["default" => $map["additionalCssClasses"]]
		]);

        $this->setProperties($setup);

        // Build probe objects
        $itemsProbe = $map["itemsClassName"]::build();
        $itemProbe = $itemsProbe->getItemClassName()::build();
        $itemFields = $itemProbe->getFields();

        // Build columns
        while (list($columnName, $columnData) = each($map["columns"])) {
            if ($columnData["fieldName"]) {
                if (!$itemField = $itemFields[$columnData["fieldName"]])
                    continue;
            }
            else
                unset($itemField);
            $columns[$columnName] = [
                "title" => $columnData["title"] ? $columnData["title"] : $itemField["title"],
                "align" => $columnData["align"] ? $columnData["align"] : ($itemField["align"] ? $itemField["align"] : ($columnData["fieldName"] ? $this->guessFieldAlignBasedOnFieldType($itemField["type"]) : null))
            ];
        }
        reset($map["columns"]);

        $html = "<div id=\"".$this->domId."\"></div>";
        
        $e->HtmlDocument->addInlineJavascript("$('#".$setup["domId"]."').UiComponentTableAdmin({
            title: '".$this->title."',
            style: '".(is_array($this->style) ? implode(" ", $this->style) : $this->style)."',
            additionalCssClasses: '".(is_array($this->additionalCssClasses) ? implode(" ", $this->additionalCssClasses) : $this->additionalCssClasses)."',
            mapName: '".$mapName."',
            columns: ".json_encode($columns).",
            ajaxUrls: {
                getRows: '".$e->Actions->getAction("TableAdminGetRows")->request->buildUrl([
                    "parameterValues" => [
                        "mapName" => $mapName,
                        "additionalFillFromParameters" => json_encode($setup["additionalFillFromParameters"])
                    ]
                ])."'
            }
        });");

		return $html;
    }
    
    /**
     * Determines the best alignment for an Item field on a table, depending on its type
     * @param integer $fieldType One of the \Cherrycake\Modules\DATABASE_FIELD_TYPE_*
     * @return integer One of the TABLE_COLUMN_ALIGN_*, depending on the specified $fieldType
     */
    function guessFieldAlignBasedOnFieldType($fieldType) {
        switch ($fieldType) {
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TINYINT:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_FLOAT:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP:
                return "right";
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATE:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIMESTAMP:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIME:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_YEAR:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_BOOLEAN:
            case \Cherrycake\Modules\DATABASE_FIELD_TYPE_COLOR:
                return "center";
            default:
                return "left";
        }
    }
}