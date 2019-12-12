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

		foreach ($request->levels as $levelName => $levelValue) {
			switch ($levelName) {
				case "country":
					$countries = Location::getCountries();
					foreach ($countries as $country) {
						$data[$levelName][] = [
							"id" => $country["id"],
							"name" => $country["name"]
						];
					}
					break;
				case "region":
					if (!$request->levels->country)
						break;
					if ($regions = Location::getRegions($request->levels->country)) {
						foreach ($regions as $region) {
							$data[$levelName][] = [
								"id" => $region["id"],
								"name" => $region["name"]
							];
						}
					}
					break;
				case "city":
					if (!$request->levels->region)
						break;
					if ($cities = Location::getCities($request->levels->country, $request->levels->region)) {
						foreach ($cities as $city) {
							$data[$levelName][] = [
								"id" => $city["id"],
								"name" => $city["name"]
							];
						}
					}
					break;
			}
		}

		$ajaxResponse = new \Cherrycake\AjaxResponseJson([
			"code" => \Cherrycake\AJAXRESPONSEJSON_SUCCESS,
			"data" => $data
		]);
		$ajaxResponse->output();
	}
}