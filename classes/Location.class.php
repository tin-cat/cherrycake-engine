<?php

/**
 * Location
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const LOCATION_DATABASE_PROVIDER_NAME = "main"; // The name of the DatabaseProvider to use when requesting location data from the Database, as defined in database.config.php
const LOCATION_CACHE_PROVIDER_NAME = "fast"; // The name of the CacheProvider to use, as defined in cache.config.php
const LOCATION_CACHE_TTL = 2592000; // TTL For the location data (2592000 = 1 Month)

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
			if (!$this->data["city"] = $this->loadCity($data["cityId"]))
				return false;

			if ($this->data["city"]["regions_id"]) {
				if (!$this->data["region"] = $this->loadRegion($this->data["city"]["regions_id"]))
					return false;

				if (!$this->data["country"] = $this->loadCountry($this->data["region"]["countries_id"]))
					return false;
			}
			else
				if (!$this->data["country"] = $this->loadCountry($this->data["city"]["countries_id"]))
					return false;

			return true;
		}
		return false;
	}

	/**
	 * Loads the country that matches the passed $code
	 * 
	 * @param string $code The country code in ISO 3166-1 Alpha 2 format
	 * @return boolean True if the country was found, false otherwise.
	 */
	function loadCountryFromCode($code) {
		if (!$country = $this->getCountryFromCode($code))
			return false;
		$this->data["country"] = $country;
		return true;
	}

	/**
	 * Returns the data from the country specified by the given $countryCode
	 * @param string $countryCode The country code in ISO 3166-1 Alpha 2 format
	 * @return array The data about the specified country
	 */
	function getCountryFromCode($countryCode) {
		global $e;
		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->queryCache(
			"select * from cherrycake_location_countries where code = '".$e->Database->$databaseProviderName->safeString($countryCode)."'",
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationCountryCode_".$countryCode
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getRow()->getData();
	}

	/**
	 * loadCountry
	 *
	 * @param integer $countryId The country id
	 * @return array The data about the specified country
	 */
	function loadCountry($countryId) {
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
	 * loadRegion
	 *
	 * @param integer $regionId The region id
	 * @return array The data about the specified region
	 */
	function loadRegion($regionId) {
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
	 * loadCity
	 *
	 * @param integer $cityId The city id
	 * @return array The data about the specified city
	 */
	function loadCity($cityId) {
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
	 * @return mixed The data about the country on this location
	 */
	function getCountry() {
		return $this->data["country"];
	}

	/**
	 * @return mixed The data about the region on this location
	 */
	function getRegion() {
		return $this->data["region"];
	}

	/**
	 * @return mixed The data about the city on this location
	 */
	function getCity() {
		return $this->data["city"];
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

	/**
	 * @return array A hash array of all the countries ordered by name, where each key is the country id
	 */
	static function getCountries() {
		global $e;
		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->queryCache(
			"select * from cherrycake_location_countries order by name asc",
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationCountries"
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getData();
	}

	/**
	 * @param integer $countryId If set, only regions for this country will be returned
	 * @return array A hash array of all the regions ordered by name, where each key is the region id
	 */
	static function getRegions($countryId = false) {
		global $e;

		if ($countryId) {
			$sql = "select * from cherrycake_location_regions where countries_id = ? order by name asc";
			$fields = [
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $countryId
				]
			];
		}
		else {
			$sql = "select * from cherrycake_location_regions order by name asc";
			$fields = false;
		}

		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->prepareAndExecuteCache(
			$sql,
			$fields,
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationRegions_".$countryId
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getData();
	}

	/**
	 * @param integer $countryId If set, only cities for this country will be returned
	 * @param integer $regionId If set, only cities for this region will be returned
	 * @return array A hash array of all the cities ordered by name, where each key is the city id
	 */
	static function getCities($countryId = false, $regionId = false) {
		global $e;

		if ($regionId && $countryId) {
			$sql = "select * from cherrycake_location_cities where countries_id = ? and regions_id = ? order by name asc";
			$fields = [
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $countryId
				],
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $regionId
				]
			];
		}
		else
		if ($regionId) {
			$sql = "select * from cherrycake_location_cities where regions_id = ? order by name asc";
			$fields = [
				[
					"type" => \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER,
					"value" => $regionId
				]
			];
		}
		else {
			$sql = "select * from cherrycake_location_cities order by name asc";
			$fields = false;
		}

		$databaseProviderName = LOCATION_DATABASE_PROVIDER_NAME;
		if (!$result = $e->Database->$databaseProviderName->prepareAndExecuteCache(
			$sql,
			$fields,
			LOCATION_CACHE_TTL,
			[
				"cacheUniqueId" => "locationCities_".$regionId
			],
			LOCATION_CACHE_PROVIDER_NAME
		))
			return false;
		return $result->getData();
	}
}