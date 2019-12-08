<?php

/**
 * UiComponentItemAdmin
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to admin an Item. Works in conjunction with the TableAdmin module.
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
					"name" => $fieldName,
                    "title" => $fieldData["title"] ? $fieldData["title"] : ($item->getFields()[$fieldName]["title"] ? $item->getFields()[$fieldName]["title"] : false),
                    "value" => $item->$fieldName,
                    "additionalCssClasses" => "fullWidth",
                    "saveAjaxUrl" => $e->Actions->getAction("ItemAdminSave".ucfirst($mapName))->request->buildUrl(["parameterValues" => [
                        $map["idRequestParameter"]->name => $id
                    ]]),
                    "saveAjaxKey" => $fieldName,
                    "isMultilanguage" => $itemFieldData["isMultiLanguage"]
                ];

				// If we don't have an specific formItem for this field, infer the most appropriate one from its type
				if (isset($itemFieldData["formItem"])) {
					$formItem = $itemFieldData["formItem"];
				} else {
					switch ($itemFieldData["type"]) {
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER:
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TINYINT:
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_FLOAT:
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_YEAR:
							$formItem = [
								"type" => \Cherrycake\Modules\FORM_ITEM_TYPE_NUMERIC
							];
							break;
							
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING:
							$formItem = [
								"type" => \Cherrycake\Modules\FORM_ITEM_TYPE_STRING
							];
							break;
						
						case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TEXT:
							$formItem = [
								"type" => \Cherrycake\Modules\FORM_ITEM_TYPE_TEXT
							];
							break;
					}
				}

				// Build the appropriate UiComponentForm item based on the $formItem setup
                switch ($formItem["type"]) {
                    case \Cherrycake\Modules\FORM_ITEM_TYPE_NUMERIC:
                        $uiComponentFormItem = \Cherrycake\UiComponentFormInputAjax::build($buildSetup);
                        break;
                        
                    case \Cherrycake\Modules\FORM_ITEM_TYPE_STRING:
                        $uiComponentFormItem = \Cherrycake\UiComponentFormInputAjax::build($buildSetup);
                        break;
                    
                    case \Cherrycake\Modules\FORM_ITEM_TYPE_TEXT:
                        $uiComponentFormItem = \Cherrycake\UiComponentFormTextAjax::build($buildSetup);
                        break;
					
					case \Cherrycake\Modules\FORM_ITEM_TYPE_SELECT:
						$buildSetup["items"] = $formItem["items"];
						switch ($formItem["selectType"]) {
							case \Cherrycake\Modules\FORM_ITEM_SELECT_TYPE_RADIOS:
								$uiComponentFormItem = \Cherrycake\UiComponentFormRadiosAjax::build($buildSetup);
								break;
							case \Cherrycake\Modules\FORM_ITEM_SELECT_TYPE_COMBO:
								break;
						}
						break;
                }
            }

            if ($fieldData["group"])
                $items[$fieldData["group"]][$fieldName] = $uiComponentFormItem;
            else
                $items[$fieldName] = $uiComponentFormItem;
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