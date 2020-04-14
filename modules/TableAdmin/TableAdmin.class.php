<?php

/**
 * TableAdmin
 *
 * @package Cherrycake
 */

namespace Cherrycake\Modules;

/**
 * TableAdmin
 *
 * A module to admin tables. Works in conjunction with the UiComponentTableAdmin Ui component.
 *
 * @package Cherrycake
 * @category Modules
 */
class TableAdmin extends \Cherrycake\Module {
	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Login" // We make TableAdmin dependent of the Login module, because it loads the logged user, and the User object might very well set up some important things that will be most probably needed to format the data shown on the table via User::afterLoginInit, like setting the timezone of the user via Locale::setTimezone
	];

    /**
     * @var array $maps Contains the mapped admins
     */
    private $maps;

	function init() {
		if (!parent::init())
			return false;
		
		global $e;
		$e->callMethodOnAllModules("mapTableAdmin");

		return true;
	}

    public static function mapActions() {
        global $e;
		$e->Actions->mapAction(
			"TableAdminGetRows",
			new \Cherrycake\ActionAjax([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE,
				"moduleName" => "TableAdmin",
				"methodName" => "getRows",
				"request" => new \Cherrycake\Request([
                    "isSecurityCsrf" => true,
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "TableAdmin"
                        ]),
                        new \Cherrycake\RequestPathComponent([
                            "type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING,
                            "name" => "mapName",
                            "securityRules" => [
                                \Cherrycake\SECURITY_RULE_NOT_EMPTY,
                                \Cherrycake\SECURITY_RULE_SLUG
                            ]
                        ]),
                        new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "getRows"
                        ])
                    ],
                    "parameters" => [
						new \Cherrycake\RequestParameter([
							"name" => "additionalFillFromParameters",
                            "type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET
                        ])
					]
				])
			])
		);
    }

    /**
     * Maps a new admin. Should be called within the mapTableAdmin method of your module, like this:
     * <code>
     * $e->TableAdmin->map("published", [
     *  "itemsClassName" => "\\CherrycakeApp\\MyItems",
     * "number" => ["fieldName" => "number"],
     * "profileImage" => [
     *     "align" => "center",
     *     "representFunction" => function ($item) {
     *          return [
     *              "type" => "image",
     *              "html" => "<img src=\"".$item->getImageMain(true)->getUrl("small")."\" />"
     *          ];
     *      }
     *     ],
     *     "name" => ["fieldName" => "name"]
     *  ],
     *  "fillFromParameters" => [
     *   "preset" => "all"
     *  ],
     *  "preCheckCallback" => function() {
     *   global $e;
     *   $e->loadCherrycakeModule("Login");
     *   return $e->UserLogin->requireUserPermission("Master");
     *  },
     *  "additionalFillFromRequestParameters" => [
     *      new \Cherrycake\RequestParameter([
     *          "name" => "nominatedByHumanId",
     *          "securityRules" => [SECURITY_RULE_TYPICAL_ID]
     *      ])
     * ]
     *]);
     * </code>
     * 
     * @param string $name The name of the admin map
     * @param array $setup A hash array of the following options for the admin:
     * * itemsClassName: The name of the class in your project that extends the Item object
     * * columns: A hash array of the columns for the table, where each key is the column name, and the value can be an array with the following keys:
     * * * fieldName: If the column must show one of the fields of the Item objects, the field name as defined on the Item's fields.
     * * * title: The title of the field. If specified, it will override the one specified on the Item's fields, if any.
     * * * align: The alignment of the field, either "left", "center" or "right". If specified, it will override the one specified on the Item's fields, if any. If no specification in the item's fields, it will automatically guess it based on the field type.
     * * * representFunction: An anonymous function that will be passed the Item object, the returned value will be shown in the column. This is used even if fieldName is specified. The function might return an HTML string, or an array with the following possible keys:
     * * * * html: The HTML
     * * * * type: The type of the table cell, for example: "image"
     * * fillFromParameters: A hash array of the parameters that have to be passed to the fillFromParameters method on the specified itemsClassName in order to retrieve the items to admin
     * * preCheckCallback: An optional anonymous function that will be called before any operation is done, and that must return true if the operation can continue, or false if it should halt for whatever reason. Usually used to check for a logged user with permissions enough to admin.
     * * additionalFillFromRequestParameters: If this admin admits additional request parameters (for example, for filtering results). Each item on this array is a RequestParameter object with the name of the additional parameter and any option securityRules or filters
     */
    function map($name, $setup) {
        global $e;
        $this->maps[$name] = $setup;
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
     * @param array $setup A hash array of options, amongst the following keys:
     * * title: The title of the table
     * * style: The style of the table admin
     * * additionalCssClasses: Additional CSS classes for the table admin
     * * additionalFillFromParameters: If the mapName admin map expects an additional parameter, this is a hash array of the expected parameter values where each key is the parameter name.
     * @return mixed The HTML code to show the table admin interface. False if the map doesn't exists.
     */
    function getHtml($mapName, $setup = false) {
        if (!$map = $this->getMap($mapName))
            return false;
        global $e;
        return $e->Ui->getUiComponent("UiComponentTableAdmin")->buildHtml($mapName, $setup);
    }

    /**
     * Outputs the json for the ajax query to retrieve rows
     * @param Request $request The request object
     * @return boolean True if the action could be attended, false otherwise
     */
    function getRows($request) {    
        if (!$map = $this->getMap($request->mapName))
            return false;

        if ($map["preCheckCallback"]) {
            if (!$map["preCheckCallback"]())
                return false;
        }
        
        $itemsProbe = $map["itemsClassName"]::build();
        $itemProbe = $itemsProbe->getItemClassName()::build();
        $idFieldName = $itemProbe->idFieldName;

        $fillFromParameters = $map["fillFromParameters"];

        // Gets additional fillFromParameters based on the requested additionalFillFromParameters and the additionalFillFromRequestParameters configured on the admin map 
        if ($request->additionalFillFromParameters && is_array($map["additionalFillFromRequestParameters"])) {
            $additionalFillFromParameters = json_decode($request->additionalFillFromParameters, true);
            if (is_array($additionalFillFromParameters)) {
                while (list($parameterKey, $parameterValue) = each($additionalFillFromParameters)) {
                    $isFound = false;
                    foreach ($map["additionalFillFromRequestParameters"] as $requestParameter) {
                        if ($requestParameter->name == $parameterKey) {
                            $isFound = true;
                            $requestParameter->setValue($parameterValue);
                            $result = $requestParameter->checkValueSecurity();
                            if (!$result->isOk) {
                                global $e;
                                $e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
                                    "errorDescription" => "From TableAdmin: ".implode(" / ", $result->description),
                                    "errorVariables" => [
                                        "mapName" => $request->mapName,
                                        "additionalFillFromRequestParameter name" => $parameterKey,
                                        "additionalFillFromRequestParameter value" => $parameterValue
                                    ]
                                ]);
                                return false;
                            }
                        }
                    }
                    reset($map["additionalFillFromRequestParameters"]);
                    if ($isFound)
                        $fillFromParameters[$parameterKey] = $parameterValue;
                }
            }
        }

        $items = $map["itemsClassName"]::build([
            "fillMethod" => "fromParameters",
            "p" => $fillFromParameters
        ]);

        if ($items->isAny()) {
            foreach ($items as $item) {
                unset($row);
                $row["id"] = $item->$idFieldName;
                while (list($columnName, $columnData) = each($map["columns"])) {
                    // If the column must contain an Item's field
                    if ($columnData["fieldName"]) {
                        if (!isset($item->getFields()[$columnData["fieldName"]]))
                            continue;
                        // If we have a fieldName and also a callback
                        if ($columnData["representFunction"])
                            $row[$columnName] = $columnData["representFunction"]($item);
                        else
                            $row[$columnName] = $item->getHumanized($columnData["fieldName"], [
                                "isHtml" => true,
                                "isEmoji" => true,
                                "isUiComponentIcons" => true
                            ]);
                    }
                    else
                    // If the column must contain the result of a callback
                    if ($columnData["representFunction"])
                        $row[$columnName] = $columnData["representFunction"]($item);
                }
                reset($map["columns"]);
                $rows[] = $row;
            }
        }

        $ajaxResponse = new \Cherrycake\AjaxResponseJson([
            "code" => \Cherrycake\AJAXRESPONSEJSON_SUCCESS,
            "data" => [
                "rows" => $rows
            ]
        ]);
        $ajaxResponse->output();
    }
}