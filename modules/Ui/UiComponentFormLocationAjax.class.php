<?php

/**
 * UiComponentFormLocationAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A Ui component to select country, region and city
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormLocationAjax extends UiComponentFormMultilevelSelectAjax {
	protected $dependentCherrycakeUiComponents = [
		"UiComponentFormMultilevelSelectAjax"
	];

	function init() {
		global $e;
		// Adds an action to retrieve location data via ajax
		$e->Actions->mapAction(
			"uiComponentFormLocationAjaxGetLocationData",
			new \Cherrycake\ActionAjax([
				"moduleType" => \Cherrycake\ACTION_MODULE_TYPE_CHERRYCAKE_UICOMPONENT,
				"moduleName" => "UiComponentFormLocationAjax",
				"methodName" => "getLocationData",
				"request" => new \Cherrycake\Request([
					"pathComponents" => [
						new \Cherrycake\RequestPathComponent([
							"type" => \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED,
							"string" => "uiComponentFormLocationAjaxGetLocationData"
						])
					],
					"parameters" => [
						new \Cherrycake\RequestParameter([
							"name" => "levels",
							"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_POST,
							"securityRules" => [
								\Cherrycake\SECURITY_RULE_NOT_NULL
							],
							"filters" => [
								\Cherrycake\SECURITY_FILTER_JSON
							]
						])
					]
				])
			])
		);
	}

	/**
	 * Builds the HTML of the input. Any setup keys can be given, which will overwrite the ones (if any) given when constructing the object.
	 *
	 * @param array $setup A hash array with the setup keys. Refer to constructor to see what keys are available.
	 */
	function buildHtml($setup = false) {
		$this->levels["country"] = array_merge($this->levels["country"], [
			"tableName" => "cherrycake_location_countries",
			"idFieldName" => "id",
			"titleFieldName" => "name",
			"style" => $this->levels["country"]["style"]." fullWidth"
		]);

		$this->levels["region"] = array_merge($this->levels["region"], [
			"tableName" => "cherrycake_location_regions",
			"idFieldName" => "id",
			"previousLevelIdFieldName" => "countries_id",
			"titleFieldName" => "name",
			"style" => $this->levels["country"]["style"]." fullWidth"
		]);

		$this->levels["city"] = array_merge($this->levels["city"], [
			"tableName" => "cherrycake_location_cities",
			"idFieldName" => "id",
			"previousLevelIdFieldName" => "regions_id",
			"titleFieldName" => "name",
			"style" => $this->levels["country"]["style"]." fullWidth"
		]);

		$this->actionName = "uiComponentFormLocationAjaxGetLocationData";

		return parent::buildHtml($setup);
	}

	function getLocationData($request) {
		global $e;

		if (!$request->levels) {
			$ajaxResponse = new \Cherrycake\AjaxResponseJson([
				"code" => \Cherrycake\AJAXRESPONSEJSON_ERROR
			]);
			$ajaxResponse->output();
			return;
		}

		foreach ($request->levels as $levelName => $levelData) {
			switch ($levelName) {
				case "country":
					$data = Location::getCountries();
					break;
				case "region":
					break;
				case "city":
					break;
			}
			$data[$levelName] = $levelData;
		}

		$ajaxResponse = new \Cherrycake\AjaxResponseJson([
			"code" => \Cherrycake\AJAXRESPONSEJSON_SUCCESS,
			"data" => $data
		]);
		$ajaxResponse->output();
	}
}