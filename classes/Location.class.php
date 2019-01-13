<?php

/**
 * Location
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Location
 *
 * Class that represents a location
 *
 * @package Cherrycake
 * @category Classes
 */
class Location {
	/**
	 * @var $data The data of this location
	 */
	var $data;

	/**
	 * __construct
	 *
	 * Constructor, allows to create an instance object which automatically fills itself in one of the available forms
	 *
	 * Setup keys:
	 *
	 * * loadMethod: If specified, it loads the Item using the given method, available methods:
	 * 	- fromGivenLocationIds: Loads the Location for the given countryId, RegionId and CityId keys
	 *
	 * @param array $setup Specifications on how to create the Location object
	 *
	 * @return boolean Whether the object could be initialized ok or not
	 */
	function __construct($setup = false) {
		if (!$setup)
			return true;

		if ($setup["loadMethod"])
			switch($setup["loadMethod"]) {
				case "fromGivenLocationIds":
					return $this->loadFromGivenLocationIds([
						"countryId" => $setup["countryId"],
						"regionId" => $setup["regionId"],
						"cityId" => $setup["cityId"]
					]);
					break;
			}

		return true;
	}

	/**
	 * loadFromGivenLocationIds
	 *
	 * Loads this Location using the given countryId, regionId and cityId data keys on the $data array
	 * countryId and regionId can be overriden if cityId is specified.
	 * countryId can be overriden if regionId and cityId is specified.
	 * Takes into account that some cities may no be associated to a region, but only to a country
	 *
	 * @param array $data The location ids
	 * @return boolean True on success, false otherwise
	 */
	function loadFromGivenLocationIds($data) {
		if ($data["cityId"]) {
			if (!$this->data["city"] = $this->getCity($data["cityId"]))
				return false;

			if ($this->data["city"]["regions_id"]) {
				if (!$this->data["region"] = $this->getRegion($this->data["city"]["regions_id"]))
					return false;

				if (!$this->data["country"] = $this->getCountry($this->data["region"]["countries_id"]))
					return false;
			}
			else
				if (!$this->data["country"] = $this->getCountry($this->data["city"]["countries_id"]))
					return false;

			return true;
		}
		return false;
	}

	/**
	 * getCountry
	 *
	 * @param integer $countryId The country id
	 * @return array The data about the specified country
	 */
	function getCountry($countryId) {
		global $e;
		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->queryCache(
			"select * from cherrycake_location_countries where id = ".$e->Database->$databaseProviderName->safeString($countryId),
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationCountry_".$countryId
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getRow()->getData();
	}

	/**
	 * getRegion
	 *
	 * @param integer $regionId The region id
	 * @return array The data about the specified region
	 */
	function getRegion($regionId) {
		global $e;
		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->queryCache(
			"select * from cherrycake_location_regions where id = ".$e->Database->$databaseProviderName->safeString($regionId),
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationRegion_".$regionId
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getRow()->getData();
	}

	/**
	 * getCity
	 *
	 * @param integer $cityId The city id
	 * @return array The data about the specified city
	 */
	function getCity($cityId) {
		global $e;
		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->queryCache(
			"select * from cherrycake_location_cities where id = ".$e->Database->$databaseProviderName->safeString($cityId),
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationCity_".$cityId
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getRow()->getData();
	}

	/**
	 * getName
	 *
	 * Returns a string representation of the location
	 *
	 * Setup keys:
	 *
	 * * isOnlyCityWhenImportantCity: Whether to show only the city name if it's an important city. Defaults to false.
	 * * isCity: Whether to include the city name or not. Defaults to true.
	 * * isRegion: Whether to include the region name or not. Defaults to false.
	 * * isCountry: Whether to include the country name or not. Defaults to true.
	 *
	 * @param array $setup Setup options on how to build the name
	 * @return string A string representation of the location
	 */
	function getName($setup = false) {
		if (!isset($setup["isOnlyCityWhenImportantCity"]))
			$setup["isOnlyCityWhenImportantCity"] = true;

		if (!isset($setup["isCity"]))
			$setup["isCity"] = true;

		if (!isset($setup["isRegion"]))
			$setup["isRegion"] = false;

		if (!isset($setup["isCountry"]))
			$setup["isCountry"] = true;

		if ($this->data["city"] && $setup["isCity"]) {
			$r = $this->data["city"]["name"];
			if ($this->data["city"]["isImportant"] && $setup["isOnlyCityWhenImportantCity"])
				return $r;
		}

		if ($this->data["region"] && $setup["isRegion"])
			$r = ($r ? $r.", " : null).$this->data["region"]["name"];

		if ($this->data["country"] && $setup["isCountry"])
			$r = ($r ? $r.", " : null).$this->data["country"]["name"];

		return $r;
	}
}