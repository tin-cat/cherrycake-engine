<?php

/**
 * ItemAdmin
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

const FORM_ITEM_TYPE_NUMERIC = 0;
const FORM_ITEM_TYPE_STRING = 1;
const FORM_ITEM_TYPE_TEXT = 2;
const FORM_ITEM_TYPE_BOOLEAN = 3;
const FORM_ITEM_TYPE_RADIOS = 4;
const FORM_ITEM_TYPE_SELECT = 5;
const FORM_ITEM_TYPE_DATABASE_QUERY = 6;
const FORM_ITEM_TYPE_COUNTRY = 7;

const FORM_ITEM_META_TYPE_MULTILEVEL_SELECT = 0;
const FORM_ITEM_META_TYPE_LOCATION = 1;

/**
 * ItemAdmin
 *
 * A module to admin items.
 * It allows the creation of HTML forms in conjunction with the UiComponentItemAdmin, and also simplifies the process of receiving data for an Item via a request, validating the values and storing them.
 *
 * @package Cherrycake
 * @category Modules
 */
class ItemAdmin extends \Cherrycake\Module {
	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Validate"
	];

    /**
     * @var array $maps Contains the mapped admins
     */
    private $maps;

	function init() {
		if (!parent::init())
			return false;

		global $e;
		$e->callImplementedStaticMethodOnAllAvailableModulesAndLoad("mapItemAdmin");
		
		return true;
	}

	public static function mapActions() {
	}

    /**
     * Maps a new item admin.
     * The associated Action will be called with the ItemAdminSave<Map name>, being <Map name> the passed $name parameter with the first letter capitalized.
     * This map will be used by UiComponentItemAdmin when automatically creating forms for editing items, and is designed to also work in a more manual way by specifying this Action to the UiComponentFormInputAjax saveAjaxUrl parameter.
     * 
     * Should be called within the mapActions method of your module, like this:
     * <code>
     * $e->loadCherrycakeModule("ItemAdmin");
     * $e->ItemAdmin->map("mymap", [
     * ]);
     * </code>
     * 
     * @param string $name The name of the admin map
     * @param array $setup A hash array of the following options for the admin:
     * * itemClassName: The name of the class in your project that extends the Item object
     * * idFieldName: The name of the id field of the Item that will be used to uniquely identify it. If not specified, the idFieldName specified in the Item class will be used.
     * * idRequestParameter: A RequestParameter object that will receive the id of the Item.
     * * additionalRequestParameters: An array of RequestParameter objects for any additional request parameters. Additional parameter names must be different than field names.
     * * fields: A hash array of the fields that will be treated, where each key is the Item's field name as defined in Item::fields, and the value can be null or an array with the following keys. Keys specified here will override the ones specified in the Item fields setup.
     * * * group: To make multiple fields appear grouped, set this to a group identifier. Normally, fields in the same group will appear in an horizontal grid in the form.
     * * * title: The title of the field. If specified, it will override the one specified on the Item's fields, if any.
     * * * isEdit: Whether this field must be editable or not. Default is true.
     * * * representFunction: An anonymous function that will be passed the Item object, the returned value will be shown to represent this field current value.
     * * * saveFunction: An anonymous function to save the passed value.
     * * * requestSecurityRules: An array of security rules from the available \Cherrycake\SECURITY_RULE_*, just like the RequestParameter class accepts.
     * * * requestFilters: An array of filter from the available SECURITY_FILTER_*, just like the RequestParameter class accepts.
	 * * * validations: An array of validations to perform to the value from the available VALIDATE_* from the Validate module.
     * * * validationMethod: An anonymous function to validate the received value for this field, or an array where the first element is the class name, and the second the method name, just like the call_user_func PHP function would expect it. Must return an AjaxResponse object.
     * * * onValid: An anonymous function that will be executed when this field is received and is validated ok, it receives as parameters: The Request object, the Item object, the field name and the received value.
     * * * onInvalid: Same as onValid, but when the field fails validation.
     * * preCheckCallback: An optional anonymous function that will be called before any operation is done, and that must return a Result object to determine if the operation can continue. Usually used to check for a logged user with permissions enough to admin.
     * * onValid: An anonymous function that will be executed when any field is received, is validated ok and no specific onValid function has been set up for that field. It receives as parameters: The Request object, the Item object, the field name and the received value.
     * * onInvalid: Same as onValid, but when the field fails validation. 
     */
    function map($name, $setup) {
        global $e;
        $this->maps[$name] = $setup;

        // Create a probe Item
        $itemProbe = $setup["itemClassName"]::build();

        // Build the parameters array
        if (is_array($setup["additionalRequestParameters"]))
            $parameters = $setup["additionalRequestParameters"];
        
        $parameters[] = $setup["idRequestParameter"];
            
        // Prepare the array of parameters for the action to be able to receive data for all Item fields
		$itemProbeFields = $itemProbe->getFields();
		while (list($fieldName, $fieldData) = each($itemProbeFields)) {
			if (is_array($setup["fields"][$fieldName]))
				$fieldData = array_merge($setup["fields"][$fieldName], is_array($fieldData) ? $fieldData : []);
            $parameters[] = 
                new \Cherrycake\RequestParameter([
                    "name" => $fieldName,
                    "type" => \Cherrycake\REQUEST_PARAMETER_TYPE_POST,
                    "securityRules" => $fieldData["requestSecurityRules"] ? $fieldData["requestSecurityRules"] : [\Cherrycake\SECURITY_RULE_SQL_INJECTION],
                    "filters" => $fieldData["requestFilters"]
                ]);
        }

        $e->Actions->mapAction(
			"ItemAdminSave".ucfirst($name),
			new \Cherrycake\ActionAjax([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
				"moduleName" => "ItemAdmin",
				"methodName" => "save",
				"request" => new \Cherrycake\Request([
                    "isSecurityCsrf" => true,
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "ItemAdmin"
                        ]),
                        new \Cherrycake\RequestPathComponent([
                            "type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => $name
                        ]),
                        new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "save"
                        ])
                    ],
                    "parameters" => $parameters
				])
			])
        );
    }

    /**
     * @param string $map The name of the map to get.
     * @return mixed A hash array with the specified map options, or false if the map doesn't exists.
     */
    function getMap($name) {
        if (!isset($this->maps[$name]))
            return false;
        return $this->maps[$name];
    }

    /**
     * @param string $mapName The name of the admin map to get the HTML for. Must've been defined previously by calling the map method.
     * @param mixed $id The unique identified of the item to edit.
     * @param array $setup A hash array of options, amongst the following keys:
     * * title: A title for the form
     * * style: The style of the table admin
     * * additionalCssClasses: Additional CSS classes for the table admin
     * @return mixed The HTML code to show the table admin interface. False if the map doesn't exists.
     */
    function getHtml($mapName, $id, $setup = false) {
        if (!$map = $this->getMap($mapName))
            return false;
        global $e;
        return $e->Ui->getUiComponent("UiComponentItemAdmin")->buildHtml($mapName, $id, $setup);
    }

    /**
     * Receives an ajax request to treat one or more values for an specific item, in conjunction with UiComponents like UiComponentItemAdmin or UiComponentFormInputAjax.
     * Its configuration is set up by previously setting an ItemAdmin map by calling the map method in an app module's mapAction method.
     * It then gos through all the possible passed field values as defined on the map's fields, and validates them. If they validate ok, it executes the specified onValid methods, or tries to save the value for the Item's field.
     * Finally, it outputs an AjaxResponseJson with the result of the operation, containing also the errors of all the validations, if any.
     * 
     * @param Request $request The request object
     * @return boolean True if the action could be attended, false otherwise
     */
    function save($request) {
        global $e;

        // Retrieves the ItemAdmin map that matches the current action name by its syntax
        if (!$map = $this->getMap(lcfirst(substr($e->Actions->currentActionName, strlen("ItemAdminSave")))))
            return false;

        // Get item
        $item = $map["itemClassName"]::build([
            "loadMethod" => "fromId",
            "idFieldName" => $map["idParameterName"],
            "id" => $request->{$map["idRequestParameter"]->name}
        ]);

        if ($map["preCheckCallback"]) {
			$preCheckResult = $map["preCheckCallback"]($request, $item);
            if (!$preCheckResult->isOk) {
                $ajaxResponse = new \Cherrycake\AjaxResponseJson([
					"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
					"description" => $preCheckResult->description,
					"messageType" => \Cherrycake\AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NOTICE
				]);
				$ajaxResponse->output();
				return true;
			}
        }
        
        $itemProbe = $map["itemClassName"]::build();
        $idFieldName = $itemProbe->idFieldName;

        $errorDescriptions = [];

		$itemProbeFields = $itemProbe->getFields();
        //foreach (array_keys($map["fields"]) as $fieldName) {
		foreach ($itemProbeFields as $fieldName => $fieldData) {

			if (!$request->isParameterReceived($fieldName))
                continue;
            
			if (is_array($map["fields"][$fieldName]))
				$fieldData = array_merge($map["fields"][$fieldName], is_array($fieldData) ? $fieldData : []);
            
            $isAnyParameterPassed = true;
			$isThisFieldAnyErrors = false;

			// If we have validations, perform them
			if ($fieldData["validations"]) {
				$result = $e->Validate->isValid($request->$fieldName, $fieldData["validations"]);
				if (!$result->isOk) {
					$isThisFieldAnyErrors = true;
					$errorDescriptions = array_merge($errorDescriptions, $result->descriptions);
				}
			}

            // If we don't have a validation method, consider it valid
            if (!$fieldData["validationMethod"]) {
                $values[$fieldName] = $request->$fieldName;
                if ($fieldData["onValid"])
                    $fieldData["onValid"]($request, $item, $fieldName, $request->$fieldName);
                else
                if ($map["onValid"])
                    $map["onValid"]($request, $item, $fieldName, $request->$fieldName);
            }
            else {
                $result = call_user_func($fieldData["validationMethod"], $request->$fieldName);
                
                if ($result->isOk) {
                    if ($fieldData["onValid"])
                        $fieldData["onValid"]($request, $item, $fieldName, $request->$fieldName);
                    else
                    if ($map["onValid"])
                        $map["onValid"]($request, $item, $fieldName, $request->$fieldName);
    
                    $values[$fieldName] = $request->$fieldName;
                }
                else {
                    if ($fieldData["onInvalid"])
                        $fieldData["onInvalid"]($request, $item, $fieldName, $request->$fieldName);
                    else
                    if ($fieldData["onInvalid"])
                        $fieldData["onInvalid"]($request, $item, $fieldName, $request->$fieldName);
                    
                    $isThisFieldAnyErrors = true;
                    $errorDescriptions = array_merge($errorDescriptions, $result->descriptions);
                }
            }

			if ($isThisFieldAnyErrors)
				$isAnyErrors = true;
			else
				$item->update([$fieldName => $request->$fieldName]);
        }

        // If none of the possible keys has been passed, stop here and return an Ok response.
        if (!$isAnyParameterPassed)
            $ajaxResponse = new \Cherrycake\AjaxResponseJson(["code" => \Cherrycake\AJAXRESPONSEJSON_SUCCESS]);
        else
		if ($isAnyErrors)
			$ajaxResponse = new \Cherrycake\AjaxResponseJson([
				"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR,
				"data" => [
					"description" => (is_array($errorDescriptions) ? implode("<br>", $errorDescriptions) : false)
				]
            ]);
        else
            $ajaxResponse = new \Cherrycake\AjaxResponseJson([
                "code" => \Cherrycake\AJAXRESPONSEJSON_SUCCESS,
                "data" => ["values" => $values]
            ]);
        
        $ajaxResponse->output();
		return true;
    }
}