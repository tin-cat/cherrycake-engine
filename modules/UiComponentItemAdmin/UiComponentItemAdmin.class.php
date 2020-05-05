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
class UiComponentItemAdmin extends \Cherrycake\UiComponent {
	protected $dependentCoreModules = [
        "UiComponentAjax",
		"UiComponentForm",
		"UiComponentFormInputAjax",
		"UiComponentFormTextAjax",
		"UiComponentFormRadiosAjax",
		"UiComponentFormSelectAjax",
		"UiComponentFormDatabaseQueryAjax",
		"UiComponentFormCountryAjax",
		"UiComponentFormMultilevelSelectAjax",
		"UiComponentFormLocationAjax"		
    ];
    
    /**
     * @param string $mapName The name of the TableAdmin map to use. Must've been defined previously by calling TableAdmin::map
     * @param mixed $id The unique identifier of the item to edit.
	 * @param array $setup A hash array of setup keys for the building of the table, available keys:
     * * title: Optional title for the table.
	 * * style: The style of the table.
     * * additionalCssClasses: Additional CSS classes for the table admin.
	 * * fields: If specified, this fields arrangement will be used. If not, the "fields" arrangement specified on the map using ItemAdmin::map will be used
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

        // Build the $items array of UiComponentForm items by columns
		$fields = isset($setup["fields"]) ? $setup["fields"] : $map["fields"];
		foreach ($fields as $fieldName => $fieldData) {

			// If the fieldData has been set as false, skip it. Fields with a null fieldData are not skipped.
			if ($fieldData === false)
				continue;
			
			// If no fields or metafields are defined for this fieldName, skip it
            if (
				!($item->getFields()[$fieldName] ?? false)
				&&
				!($item->getMetaFields()[$fieldName] ?? false)
			)
                continue;
			
			if (!isset($fieldData["isEdit"]))
				$fieldData["isEdit"] = true;

			// If this field is not editable, use a special uneditable UiComponentForm item instead
			if (!$fieldData["isEdit"]) {

				$uiComponentFormItem = \Cherrycake\UiComponentFormUneditable::build([
					"title" => isset($fieldData["title"]) ? $fieldData["title"] : ($item->getFields()[$fieldName]["title"] ? $item->getFields()[$fieldName]["title"] : false),
					"value" =>
						$fieldData["representFunction"]
						?
						$fieldData["representFunction"]($item)
						:
						$item->getHumanized($fieldName, ["isHtml" => true, "isEmoji" => true, "isUiComponentIcons" => true])
				]);

			}
			// If it's editable, build the proper UiComponentForm item
			else {
            
				// If its a regular, non-meta field
				if ($itemFieldData = $item->getFields()[$fieldName] ?? false) {

					$buildSetup = [
						"name" => $fieldName,
						"domId" => uniqid(),
						"style" => $itemFieldData["formItem"]["style"] ?? false,
						"title" =>
							(
								isset($itemFieldData["formItem"]["title"]) ? $itemFieldData["formItem"]["title"] :
								(
									isset($fieldData["title"]) ? $fieldData["title"] :
									(
										$item->getFields()[$fieldName]["title"] ? $item->getFields()[$fieldName]["title"] :
										false
									)
								)
							),
						"value" => $item->$fieldName,
						"additionalCssClasses" => "fullWidth",
						"saveAjaxUrl" => $e->Actions->getAction("ItemAdminSave".ucfirst($mapName))->request->buildUrl(["parameterValues" => [
							$map["idRequestParameter"]->name => $id
						]]),
						"saveAjaxKey" => $fieldName,
						"isMultilanguage" => $itemFieldData["isMultiLanguage"] ?? false
					];

					// Build the appropriate UiComponentForm item based on the formItem setup
					unset($uiComponentFormItem);
					switch ($itemFieldData["formItem"]["type"]) {
						case \Cherrycake\FORM_ITEM_TYPE_NUMERIC:
							$uiComponentFormItem = $e->UiComponentFormInputAjax->clone($buildSetup);
							break;
							
						case \Cherrycake\FORM_ITEM_TYPE_STRING:
							$uiComponentFormItem = $e->UiComponentFormInputAjax->clone($buildSetup);
							break;
						
						case \Cherrycake\FORM_ITEM_TYPE_TEXT:
							$uiComponentFormItem = $e->UiComponentFormTextAjax->clone($buildSetup);
							break;
						
						case \Cherrycake\FORM_ITEM_TYPE_RADIOS:
							$buildSetup["items"] = $itemFieldData["formItem"]["items"];
							$uiComponentFormItem = $e->UiComponentFormRadiosAjax->clone($buildSetup);
							break;
						
						case \Cherrycake\FORM_ITEM_TYPE_SELECT:
							foreach ($itemFieldData["formItem"]["items"] as $key => $thisItem)
								$buildSetup["items"][$key] = $thisItem["title"];
							$uiComponentFormItem = $e->UiComponentFormSelectAjax->clone($buildSetup);
							break;
						
						case \Cherrycake\FORM_ITEM_TYPE_DATABASE_QUERY:
							$uiComponentFormItem = $e->UiComponentFormDatabaseQueryAjax->clone($buildSetup);
							break;
						
						case \Cherrycake\FORM_ITEM_TYPE_COUNTRY:
							$buildSetup["selectionStyle"] = $itemFieldData["formItem"]["selectionStyle"];
							$uiComponentFormItem = $e->UiComponentFormCountryAjax->clone($buildSetup);
							break;
					}
				}
				// If it's a meta field
				else if ($itemFieldData = $item->getMetaFields()[$fieldName]) {

					$buildSetup = [
						"name" => $fieldName,
						"title" => $fieldData["title"] ?? false ?: ($item->getFields()[$fieldName]["title"] ?? false ?: false),
						"value" => $item->$fieldName,
						"additionalCssClasses" => "fullWidth",
						"saveAjaxUrl" => $e->Actions->getAction("ItemAdminSave".ucfirst($mapName))->request->buildUrl(["parameterValues" => [
							$map["idRequestParameter"]->name => $id
						]]),
						"saveAjaxKey" => $fieldName,
						"isMultilanguage" => $itemFieldData["isMultiLanguage"] ?? false
					];
					
					switch ($itemFieldData["formItem"]["type"]) {
						case \Cherrycake\FORM_ITEM_META_TYPE_MULTILEVEL_SELECT:
							// Populate the levels array with the appropriate values from the $item for each level
							foreach ($itemFieldData["formItem"]["levels"] as $levelName => $levelData)
								$itemFieldData["formItem"]["levels"][$levelName]["value"] = $item->{$levelData["fieldName"]};
							reset($itemFieldData["formItem"]["levels"]);

							$uiComponentFormItem = $e->UiComponentFormMultilevelSelectAjax->clone(array_merge($buildSetup, [
								"levels" => $itemFieldData["formItem"]["levels"]
							]));
							break;
						case \Cherrycake\FORM_ITEM_META_TYPE_LOCATION:
							// Populate the levels array with the appropriate values from the $item for each level
							foreach ($itemFieldData["formItem"]["levels"] as $levelName => $levelData) {
								$itemFieldData["formItem"]["levels"][$levelName]["value"] = $item->{$levelData["fieldName"]};
								unset($itemFieldData["formItem"]["levels"][$levelName]["fieldName"]);
								$itemFieldData["formItem"]["levels"][$levelName]["saveAjaxKey"] = $levelData["fieldName"];
							}
							reset($itemFieldData["formItem"]["levels"]);

							$uiComponentFormItem = $e->UiComponentFormLocationAjax->clone(array_merge($buildSetup, [
								"levels" => $itemFieldData["formItem"]["levels"]
							]));
							break;
					}

				}
			}

            if ($fieldData["group"]  ?? false)
                $items[$fieldData["group"]][$fieldName] = $uiComponentFormItem;
            else
                $items[$fieldName] = $uiComponentFormItem;

        }
        reset($map["fields"]);

        return $e->UiComponentForm->buildHtml([
            "style" => $this->style,
            "additionalCssClasses" => $this->additionalCssClasses,
            "items" => $items,
            "title" => $this->title
        ]);
    }
}