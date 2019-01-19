<?php

/**
 * UiComponentItemAdmin
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to admin database tables. Works in conjunction with the TableAdmin module.
 * 
 * @package Cherrycake
 * @category Classes
 */
class UiComponentItemAdmin extends UiComponent {
	protected $dependentCherrycakeUiComponents = [
        "UiComponentAjax",
        "UiComponentForm"
    ];
    
    /**
     * @param string $mapName The name of the TableAdmin map to use. Must've been defined previously by calling TableAdmin::map
     * @param mixed $id The unique identified of the item to edit.
	 * @param array $setup A hash array of setup keys for the building of the table, available keys:
     * * title: Optional title for the table.
	 * * style: The style of the table.
     * * additionalCssClasses: Additional CSS classes for the table admin.
	 * @return mixed The HTML of the table admin, or false if the specified map doesn't exists.
	 */
	function buildHtml($mapName, $id, $setup = false) {
        global $e;

        if (!$map = $e->ItemAdmin->getMap($mapName))
            return false;
        
        if (!is_array($map["fields"]))
            return false;
        
        // Build Item object
        if (!$item = $map["itemClassName"]::build([
            "loadMethod" => "fromId",
            "id" => $id
        ]))
            return false;
		
		$this->treatParameters($setup, [
            "domId" => ["default" => uniqid()],
            "style" => ["default" => false],
            "additionalCssClasses" => ["default" => false],
            "title" => ["default" => false]
		]);

        $this->setProperties($setup);

        // Build columns
        while (list($fieldName, $fieldData) = each($map["fields"])) {
            if (!$item->getFields()[$fieldName])
                continue;
            
            $itemFieldData = $item->getFields()[$fieldName];

            if (!isset($fieldData["isEdit"]))
                $fieldData["isEdit"] = true;

            if (!$fieldData["isEdit"]) {
                $formItem = \Cherrycake\UiComponentFormUneditable::build([
                    "title" => $fieldData["title"] ? $fieldData["title"] : ($item->getFields()[$fieldName]["title"] ? $item->getFields()[$fieldName]["title"] : false),
                    "value" =>
                        $fieldData["representFunction"]
                        ?
                        $fieldData["representFunction"]($item)
                        :
                        $item->getHumanized($fieldName, ["isHtml" => true, "isEmoji" => true, "isUiComponentIcons" => true])
                ]);
            } else {
                $buildSetup = [
                    "title" => $fieldData["title"] ? $fieldData["title"] : ($item->getFields()[$fieldName]["title"] ? $item->getFields()[$fieldName]["title"] : false),
                    "value" => $item->$fieldName,
                    "additionalCssClasses" => "fullWidth",
                    "saveAjaxUrl" => $e->Actions->getAction("ItemAdminSave".ucfirst($mapName))->request->buildUrl(["parameterValues" => [
                        $map["idRequestParameter"]->name => $id
                    ]]),
                    "saveAjaxKey" => $fieldName,
                    "isMultilanguage" => $itemFieldData["isMultiLanguage"]
                ];

                switch ($itemFieldData["type"]) {
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER:
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TINYINT:
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_FLOAT:
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_YEAR:
                        $formItem = \Cherrycake\UiComponentFormInputAjax::build($buildSetup);
                        break;
                        
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING:
                        $formItem = \Cherrycake\UiComponentFormInputAjax::build($buildSetup);
                        break;
                    
                    case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TEXT:
                        $formItem = \Cherrycake\UiComponentFormTextAjax::build($buildSetup);
                        break;
                }
            }

            if ($fieldData["group"])
                $items[$fieldData["group"]][$fieldName] = $formItem;
            else
                $items[$fieldName] = $formItem;
        }
        reset($map["fields"]);

        return $e->Ui->getUiComponent("UiComponentForm")->buildHtml([
            "style" => $this->style,
            "additionalCssClasses" => $this->additionalCssClasses,
            "items" => $items,
            "title" => $this->title
        ]);
    }
}