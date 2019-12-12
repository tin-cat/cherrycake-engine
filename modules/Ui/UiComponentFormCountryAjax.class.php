<?php

/**
 * UiComponentFormCountryAjax
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * UiComponentFormCountryAjax
 *
 * A Ui component for form selects
 *
 * @package Cherrycake
 * @category Classes
 */
class UiComponentFormCountryAjax extends UiComponent {
	protected $style;
	protected $additionalCssClasses;
	protected $domId;
	protected $title;
	protected $name;
	protected $value;
	protected $isDisabled = false;
	protected $isAutoFocus;
	protected $onChange;

	protected $dependentCherrycakeUiComponents = [
		"UiComponentFormDatabaseQueryAjax"
	];

	function buildHtml($setup = false) {
		global $e;
		
		return $e->Ui->getUiComponent("UiComponentFormDatabaseQueryAjax")->buildHtml([
			"style" => $this->style,
			"additionalCssClasses" => $this->additionalCssClasses,
			"domId" => $this->domId,
			"title" => $this->title,
			"name" => $this->name,
			"value" => $this->value,
			"isDisabled" => $this->isDisabled,
			"isAutofocus" => $this->isAutofocus,
			"onChange" => $this->onChange,
			"querySql" => "
				select
					id,
					concat(name, ' (+', phonePrefix, ')') as title
				from
					cherrycake_location_countries
				order by
					name asc
			",
			"queryCacheKeyNamingOptions" => [
				"uniqueId" => "UiComponentFormCountryAjax_countries"
			],
			"valueFieldName" => "id",
			"titleFieldName" => "title"
		]);
	}
}