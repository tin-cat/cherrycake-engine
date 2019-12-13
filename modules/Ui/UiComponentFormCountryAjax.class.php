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
class UiComponentFormCountryAjax extends UiComponentFormDatabaseQueryAjax {
	function buildHtml($setup = false) {
		$this->setProperties($setup);
		$this->querySql = "
			select
				id,
				concat(name, ' (+', phonePrefix, ')') as title
			from
				cherrycake_location_countries
			order by
				name asc
		";
		$this->queryCacheKeyNamingOptions = [
			"uniqueId" => "UiComponentFormCountryAjax_countries"
		];
		$this->valueFieldName = "id";
		$this->titleFieldName = "title";
		return parent::buildHtml($setup);
	}
}