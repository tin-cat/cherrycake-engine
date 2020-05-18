<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * The Locale module provides localization functionalities for multilingual web sites with automatic detection, plus the handling of currencies, dates, timezones and more.
 *
 * @package Cherrycake
 * @category Modules
 */
class Locale  extends \Cherrycake\Module {
	/**
	 * @var bool $isConfig Sets whether this module has its own configuration file. Defaults to false.
	 */
	protected $isConfigFile = true;

	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		/*
			A hash array of available localizations the app supports, where each key is the locale name, and each value a hash array with the following keys:
				domains: An array of domains that will trigger this localization when the request to the app comes from one of them, or false if this is the only locale to be used always.
				language: The language used in this localization, one of the available LANGUAGE_? constants.
				dateFormat: The date format used in this localization, one of the available DATE_FORMAT_? constants.
				temperatureUnits: The temperature units used in this localization, one of the available TEMPERATURE_UNITS_? constants.
				currency: The currency used in this localization, one of the available CURRENCY_? constants.
				decimalMark: The type character used when separating decimal digits in this localization, one of the available DECIMAL_MARK_? constants.
				measurementSystem: The measurement system used in this localization, one of the available MEASUREMENT_SYSTEM_? constants.
				timeZone: The timezone id used in this localization, from the cherrycake_location_timezones table of the Cherrycake skeleton database.
		*/
		"availableLocales" =>
		[ 
			"main" => [
				"domains" => false,
				"language" => LANGUAGE_ENGLISH,
				"dateFormat" => DATE_FORMAT_MIDDLE_ENDIAN,
				"temperatureUnits" => TEMPERATURE_UNITS_FAHRENHEIT,
				"currency" => CURRENCY_USD,
				"decimalMark" => DECIMAL_MARK_POINT,
				"measurementSystem" => MEASUREMENT_SYSTEM_IMPERIAL,
				"timeZone" => TIMEZONE_ID_ETC_UTC
			]
		],
		"defaultLocale" => "main", // The locale name to use when it can not be autodetected.
		"canonicalLocale" => false, // The locale to consider canonical, used i.e. in the HtmlDocument module to set the rel="canonical" meta tag, in order to let search engines understand that there are different pages in different languages that represent the same content.
		"availableLanguages" => [LANGUAGE_ENGLISH], // An array of the languages that are available for the app. The textsTableName should contain at least this languages. From the available LANGUAGE_? constants.
		"geolocationMethod" => \Cherrycake\GEOLOCATION_METHOD_CLOUDFLARE, // The method to use to determine the user's geographical location, one of the available LOCALE_GEOLOCATION_METHOD_? constants.
		"textsTableName" => "cherrycake_locale_texts", // The name of the table where multilingual localized texts are stored. See the cherrycake_locale_texts table in the Cherrycake skeleton database.
		"textsDatabaseProviderName" => "main", // The name of the database provider where the localized multilingual texts are found
		"textCategoriesTableName" => "cherrycake_locale_textCategories", // The name of the table where text categories are stored. See the cherrycake_locale_textCategories table in the Cherrycake skeleton database.
		"textCacheProviderName" => "engine", // The name of the cache provider that will be used to cache localized multilingual texts
		"textCacheKeyPrefix" => "LocaleText", // The prefix of the keys when storing texts into cache
		"textCacheDefaultTtl" => \Cherrycake\CACHE_TTL_NORMAL, // The default TTL for texts stored into cache
		"timeZonesDatabaseProviderName" => "main", // The name of the database provider where the timezones are found
		"timeZonesTableName" => "cherrycake_location_timezones", // The name of the table where the timezones are stored. See the cherrycake_location_timezones table in the Cherrycake skeleton database.
		"timeZonesCacheProviderName" => "engine", // The name of the cache provider that will be user to cache timezones
		"timeZonesCacheKeyPrefix" => "LocaleTimeZone", // The prefix of the keys when storing timezones into cache
		"timeZonesCacheDefaultTtl" => \Cherrycake\CACHE_TTL_NORMAL // The default TTL for timezones stored into cache
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	var $dependentCoreModules = [
		"Output",
		"Errors",
		"Cache",
		"Database"
	];

	/**
	 * @var array $locale The current locale settings
	 */
	var $locale;

	private $languageNames = [
		LANGUAGE_SPANISH => [
			LANGUAGE_SPANISH => "Español",
			LANGUAGE_ENGLISH => "Spanish"
		],
		LANGUAGE_ENGLISH => [
			LANGUAGE_SPANISH => "Inglés",
			LANGUAGE_ENGLISH => "English"
		],
	];

	/**
	 * @var array $languageCodes A hash array of ISO 639-1 language codes
	 */
	private $languageCodes = [
		LANGUAGE_SPANISH => "es",
		LANGUAGE_ENGLISH => "en"
	];

	/**
	 * @var array $texts A hash array with some common texts used by this module
	 */
	private $texts = [
		"justNow" => [
			LANGUAGE_SPANISH => "justo ahora",
			LANGUAGE_ENGLISH => "just now",
		],
		"agoPrefix" => [
			LANGUAGE_SPANISH => "hace "
		],
		"agoSuffix" => [
			LANGUAGE_ENGLISH => " ago"
		],
		"minute" => [
			LANGUAGE_SPANISH => "minuto",
			LANGUAGE_ENGLISH => "minute"
		],
		"minutes" => [
			LANGUAGE_SPANISH => "minutos",
			LANGUAGE_ENGLISH => "minutes"
		],
		"hour" => [
			LANGUAGE_SPANISH => "hora",
			LANGUAGE_ENGLISH => "hour"
		],
		"hours" => [
			LANGUAGE_SPANISH => "horas",
			LANGUAGE_ENGLISH => "hours"
		],
		"day" => [
			LANGUAGE_SPANISH => "día",
			LANGUAGE_ENGLISH => "day"
		],
		"days" => [
			LANGUAGE_SPANISH => "días",
			LANGUAGE_ENGLISH => "days"
		],
		"month" => [
			LANGUAGE_SPANISH => "mes",
			LANGUAGE_ENGLISH => "month"
		],
		"months" => [
			LANGUAGE_SPANISH => "meses",
			LANGUAGE_ENGLISH => "months"
		],
		"yesterday" => [
			LANGUAGE_SPANISH => "ayer",
			LANGUAGE_ENGLISH => "yesterday"
		],
		"monthsLong" => [
			LANGUAGE_SPANISH => ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"],
			LANGUAGE_ENGLISH => ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"]
		],
		"monthsShort" => [
			LANGUAGE_SPANISH => ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"],
			LANGUAGE_ENGLISH => ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"]
		],
		"prepositionOf" => [
			LANGUAGE_SPANISH => "de",
			LANGUAGE_ENGLISH => "of"
		],
		"prepositionAt" => [
			LANGUAGE_SPANISH => "a las",
			LANGUAGE_ENGLISH => "at"
		]
	];

	/**
	 * Initializes the module. Detects and assigns the locale depending on the requested domain.
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		if (!parent::init())
			return false;
		
		if (!$this->isConfig("availableLocales"))
			return true;

		if (isset($_SERVER["SERVER_NAME"])) {
			foreach ($this->getConfig("availableLocales") as $localeName => $locale) {
				if ($locale["domains"] ?? false && in_array($_SERVER["SERVER_NAME"], $locale["domains"])) {
					$this->setLocale($localeName);
					break;
				}
			}
		}
		
		if (!$this->locale)
			$this->setLocale($this->getConfig("defaultLocale"));

		return true;
	}

	/**
	 * Sets the locale to use
	 * @param string $localeName The name of the locale to use, as specified in the availableLocales config key.
	 * @return boolean True if the locale could be set, false if the locale wasn't configured in the availableLocales config key.
	 */
	function setLocale($localeName) {
		if (!isset($this->getConfig("availableLocales")[$localeName]))
			return false;
		$this->locale = $this->getConfig("availableLocales")[$localeName];
		return true;
	}

	/**
	 * Gets the name of a language.
	 * @param integer $language The language
	 * @param boolean $setup A hash array of setup options, from the following possible keys:
	 *                       - forceLanguage: Use this language instead of the passed in $language
	 * @return mixed The language name, false if the specified language is not configured.
	 */
	function getLanguageName($language, $setup = false) {
		if (!isset($this->languageNames[$language]))
			return false;
		return $this->languageNames[$language][$setup["forceLanguage"] ?? false ?: $this->getLanguage()];
	}

	/**
	 * Gets the code of a language
	 * @param integer $language The language
	 * @return mixed The language code, or false if the specified language is not configured.
	 */
	function getLanguageCode($language = false) {
		if (!$language)
			$language = $this->getLanguage();
		if (!isset($this->languageCodes[$language]))
			return false;
		return $this->languageCodes[$language];
	}

	/**
	 * getText
	 *
	 * Gets a text from the multilingual texts database
	 *
	 * @param string $code The code of the text. Can also be specified as <category code>/<text code> in order to discern texts that are stored with the same code in different categories.
	 * @param array $setup Additional setup with the following possible keys:
	 * * variables: A hash array of the variables that must be replaced taking the text as a pattern. Every occurrence of {<key>} will be replaced with the matching value, where the value can be a string, or a hash array of values for different languages, where each key is one of the available LANGUAGE_? constants.
	 * * forceLanguage: Force the retrieval of the text on this language. If not specified, the detected language is used.
	 * * forceTextCacheTtl: Use this TTL for the text cache instead of the module configuration variable textCacheDefaultTtl
	 * * isPurifyVariables: Whether to purify values from specified variables for security purposes or not. Defaults to true.
	 * @return string The text
	 */
	function getText($code, $setup = false) {
		global $e;

		if (!isset($setup["isPurifyVariables"]))
			$setup["isPurifyVariables"] = true;

		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => $this->getConfig("textCacheKeyPrefix"),
			"uniqueId" => $code
		]);
		$cacheProviderName = $this->getConfig("textCacheProviderName");

		$availableLanguages = $this->getConfig("availableLanguages");

		if (!$data = $e->Cache->$cacheProviderName->get($cacheKey)) { // Get the text from the cache
			// If not in the cache, retrieve it from the DB
			$databaseProviderName = $this->getConfig("textsDatabaseProviderName");

			if (stristr($code, "/")) { // If we're requesting a text from an specific category
				list($categoryCode, $textCode) = explode("/", $code, 2);

				$sql = "select ";
				foreach ($availableLanguages as $language)
					$sql .= $this->getConfig("textsTableName").".text_".$this->getLanguageCode($language).",";
				reset($availableLanguages);
				$sql = substr($sql, 0, -1);
				$sql .= " from ".$this->getConfig("textsTableName").", ".$this->getConfig("textCategoriesTableName");
				$sql .= " where ".$this->getConfig("textsTableName").".textCategories_id = ".$this->getConfig("textCategoriesTableName").".id";
				$sql .= " and ".$this->getConfig("textsTableName").".code = '".$e->Database->$databaseProviderName->safeString($textCode)."'";
				$sql .= " and ".$this->getConfig("textCategoriesTableName").".code = '".$e->Database->$databaseProviderName->safeString($categoryCode)."'";
				$sql .= " limit 1";
			}
			else { // Else we're requesting a text without any category
				$sql = "select ";
				foreach ($availableLanguages as $language)
					$sql .= $this->getConfig("textsTableName").".text".$language.",";
				reset($availableLanguages);
				$sql = substr($sql, 0, -1);
				$sql .= " from ".$this->getConfig("textsTableName");
				$sql .= " where ".$this->getConfig("textsTableName").".code = '".$e->Database->$databaseProviderName->safeString($code)."'";
				$sql .= " limit 1";
			}

			$result = $e->Database->$databaseProviderName->query($sql);
			if (!$result->isAny()) {
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Requested text code not found",
					"errorVariables" => ["code" => $code],
					"isSilent" => true
				]);
				return ($e->isDevel() ? "Locale text \"".$code."\" not found" : null);
			}

			$row = $result->getRow();
			$data = $row->getData();

			// Store in cache
			$e->Cache->$cacheProviderName->set($cacheKey, $data, ($setup["forceTextCacheTtl"] ?? false ? $setup["forceTextCacheTtl"] : $this->getConfig("textCacheDefaultTtl")));
		}

		$text = $data["text_".$this->getLanguageCode($setup["forceLanguage"] ?? false ? $setup["forceLanguage"] : $this->getLanguage())];

		if ($setup["variables"] ?? false)
			foreach ($setup["variables"] as $key => $value) {
				$valueReplacement =
					is_array($value)
					?
					$value[($setup["forceLanguage"] ? $setup["forceLanguage"] : $this->getLanguage())]
					:
					$valueReplacement = $value;
				if ($setup["isPurifyVariables"])
					$valueReplacement = $e->Security->clean($valueReplacement);
				$text = str_replace("{".$key."}", $valueReplacement, $text);
			}

		return $text;
	}

	/**
	 * getFromArray
	 *
	 * Given the $data hash-array with language-dependant keys in the syntax of <language id> => <content>, it returns the proper value for the current language
	 *
	 * @param array $data The data hash-array to get the field from
	 * @param int $forceLanguage If specified, forces the retrieval of the specified language instead of the Locale language
	 * @return mixed The contents of the localized data, or false if no contents for the specified language.
	 */
	function getFromArray($data, $forceLanguage = false) {
		return $data[($forceLanguage ? $forceLanguage : $this->getLanguage())] ?? false;
	}

	/**
	 * getFieldFromDatabaseRow
	 *
	 * Given a DatabaseRow with language-dependant fields named in the syntax of <$fieldBaseName><language id>, it returns the proper field contents for the current language
	 *
	 * @param DatabaseRow $databaseRow The DatabaseRow to get the field from
	 * @param string $fieldBaseName The base name of the field, <language id> will be appended to get the corresponding localized content
	 * @param int $forceLanguage If specified, forces the retrieval of the specified language instead of the Locale language
	 * @return mixed The contents of the localized field
	 */
	function getFieldFromDatabaseRow($databaseRow, $fieldBaseName, $forceLanguage = false) {
		return $databaseRow->getField($fieldBaseName.($forceLanguage ? $forceLanguage : $this->getLanguage()));
	}

	/**
	 * Sets the date format to use
	 * @param integer $dateFormat The desired dateFormat, one of the available DATE_FORMAT_*
	 */
	function setDateFormat($dateFormat) {
		$this->locale["dateFormat"] = $dateFormat;
	}

	/**
	 * Sets the temperature units to use
	 * @param integer $temperatureUnits The desired temperature units, one of the available TEMPERATURE_UNITS_*
	 */
	function setTemperatureUnits($temperatureUnits) {
		$this->locale["temperatureUnits"] = $temperatureUnits;
	}

	/**
	 * Sets the currency to use
	 * @param integer $currency The desired currency, one of the available CURRENCY_*
	 */
	function setCurrency($currency) {
		$this->locale["currency"] = $currency;
	}

	/**
	 * Sets the decimal mark to use
	 * @param integer $decimalMark The desired decimal mark, one of the available DECIMAL_MARK_*
	 */
	function setDecimalMark($decimalMark) {
		$this->locale["decimalMark"] = $decimalMark;
	}

	/**
	 * Sets the measurement system to use
	 * @param integer $measurementSystem The desired measurement system, one of the available MEASUREMENT_SYSTEM_*
	 */
	function setMeasurementSystem($measurementSystem) {
		$this->locale["measurementSystem"] = $measurementSystem;
	}

	/**
	 * Sets the language to use
	 * @param integer $language The language
	 */
	function setLanguage($language) {
		$this->locale["language"] = $language;
	}

	/**
	 * @return integer The language that is being currently used, one of the LANGUAGE_*
	 */
	function getLanguage() {
		return $this->locale["language"];
	}

	/**
	 * @return integer The language that is being currently used, one of the LANGUAGE_*
	 */
	function getCurrency() {
		return $this->locale["currency"];
	}

	/**
	 * Sets the Timezone to use
	 * @param integer $timeZone The desired timezone, one of defined in PHP constants as specified in http://php.net/manual/en/timezones.php
	 */
	function setTimeZone($timeZone) {
		$this->locale["timeZone"] = $timeZone;
	}

	/**
	 * @return integer The timezone being used
	 */
	function getTimeZone() {
		return $this->locale["timeZone"];
	}

	/**
	 * @param integer $timezone The timezone id to obtain the name of. If not specified, the current locale timezone is used
	 * @return string The timezone name in the TZ standard
	 */
	function getTimeZoneName($timezone = false) {
		global $e;

		if (!$timezone)
			$timezone = $this->getTimeZone();

		$cacheKey = $e->Cache->buildCacheKey([
			"prefix" => $this->getConfig("timeZonesCacheKeyPrefix"),
			"uniqueId" => $timezone
		]);
		$cacheProviderName = $this->getConfig("timeZonesCacheProviderName");

		if (!$timeZoneName = $e->Cache->$cacheProviderName->get($cacheKey)) { // Get the timezone name from the cache
			// If not in the cache, retrieve it from the DB
			$databaseProviderName = $this->getConfig("textsDatabaseProviderName");

			$result = $e->Database->$databaseProviderName->query("select timezone as timeZoneName from ".$this->getConfig("timeZonesTableName")." where id = ".$e->Database->$databaseProviderName->safeString($timezone));
			if (!$result->isAny()) {
				$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
					"errorDescription" => "Requested timezone not found",
					"errorVariables" => ["timezone" => $timezone],
					"isSilent" => true
				]);
				return $e->getTimezoneName();
			}

			$row = $result->getRow();
			$timeZoneName = $row->getField("timeZoneName");

			// Store in cache
			$e->Cache->$cacheProviderName->set($cacheKey, $timeZoneName, $this->getConfig("timeZonesCacheDefaultTtl"));
		}

		return $timeZoneName;
	}

	/**
	 * Converts a given timestamp from one timezone to another.
	 *
	 * @param integer $timestamp The timestamp to convert. Expected to be in the given $fromTimezone.
	 * @param integer $toTimeZone The desired timezone, one of the PHP constants as specified in http://php.net/manual/en/timezones.php. If none specified, the current Locale timezone is used.
	 * @param bool $fromTimeZone The timezone on which the given $timestamp is considered to be in. If not specified the default cherrycake timezone is used, as set in Engine::init
	 * @return mixed The converted timestamp, or false if it couldn't be converted.
	 */
	function convertTimestamp($timestamp, $toTimeZone = false, $fromTimeZone = false) {
		if (!$timestamp)
			return false;
		
		if (!$fromTimeZone) {
			global $e;
			$fromTimeZone = $e->getTimezoneId();
		}

		if (!$toTimeZone)
			$toTimeZone = $this->getTimeZone();

		if ($fromTimeZone == $toTimeZone)
			return $timestamp;

		$dateTime = new \DateTime("@".$timestamp);

		$fromDateTimeZone = new \DateTimeZone($this->getTimeZoneName($fromTimeZone));
		$toDateTimeZone = new \DateTimeZone($this->getTimeZoneName($toTimeZone));

		$offset = $toDateTimeZone->getOffset($dateTime) - $fromDateTimeZone->getOffset($dateTime);

		return $timestamp+$offset;
	}

	/**
	 * Formats the given date.
	 * 
	 * @param int $dateTimestamp The timestamp to use, in UNIX timestamp format. The hours, minutes and seconds are considered irrelevant.
	 * @param array $setup A hash array with setup options, just like the Locale::formatTimestamp method
	 * @return string The formatted date
	 */
	function formatDate($dateTimestamp, $setup = false) {
		return $this->formatTimestamp($dateTimestamp, (is_array($setup) ? $setup : []) + [
			"fromTimeZone" => false,
			"isDay" => true,
			"isHours" => false
		]);
	}

	/**
	 * Formats the given date/time according to current locale settings.
	 * The given timestamp is considered to be in the engine's default timezone configured in Engine::init, except if the "fromTimeZone" is given via setup.
	 *
	 * @param int $timestamp The timestamp to use, in UNIX timestamp format. Considered to be in the engine's default timezone configured in Engine::init, except if the "fromTimeZone" is given via setup.
	 * @param array $setup A hash array of setup options with the following possible keys
	 * * fromTimezone: Considers the given timestamp to be in this timezone. If not specified, the timestamp is considered to be in the current Locale timestamp.
	 * * toTimezone: Converts the given timestamp to this timezone. If not specified, the given timestamp is converted to the current Locale timestamp except if the fromTimeZone setup key has been set to false.
	 * * language: If specified, this language will be used instead of the detected one. One of the available LANGUAGE_?
	 * * style: The formatting style, one of the available TIMESTAMP_FORMAT_? constants.
	 * * isShortYear: Whether to abbreviate the year whenever possible. For example: 17 instead of 2017. Default: true
	 * * isDay: Whether to include the day. Default: true
	 * * isHours: Whether to include hours and minutes. Default: false
	 * * hoursFormat: The format of the hours. One of the available HOURS_FORMAT_?. Default: HOURS_FORMAT_24
	 * * isSeconds: Whether to include seconds. Default: false
	 * * isAvoidYearIfCurrent: Whether to avoid the year if it's the current one. Default: false.
	 * * isBrief: Whether to use a brief formatting whenever possible. Default: false.
	 * * format: If specified this format as used in the date PHP function is used instead of internal formatting. Default: false.
	 * @return string The formatted timestamp
	 */
	function formatTimestamp($timestamp, $setup = false) {
		// If no fromTimeZone specified for the given timestamp, the engine TIMEZONE is assumed
		if (!isset($setup["fromTimeZone"])) {
			global $e;
			$setup["fromTimeZone"] = $e->getTimezoneId();
		}

		if (!isset($setup["style"]))
			$setup["style"] = \Cherrycake\TIMESTAMP_FORMAT_BASIC;

		if (!isset($setup["isShortYear"]))
			$setup["isShortYear"] = true;

		if (!isset($setup["isDay"]))
			$setup["isDay"] = true;

		if (!isset($setup["isHours"]))
			$setup["isHours"] = false;

		if (!isset($setup["hoursFormat"]))
			$setup["hoursFormat"] = \Cherrycake\HOURS_FORMAT_24H;

		if (!isset($setup["isSeconds"]))
			$setup["isSeconds"] = false;

		if (!isset($setup["isAvoidYearIfCurrent"]))
			$setup["isAvoidYearIfCurrent"] = false;
		
		if (!isset($setup["isBrief"]))
			$setup["isBrief"] = false;

		// Convert the given timestamp to the Locale timezone if fromTimeZone has been specified.
		if ($setup["fromTimeZone"] ?? false)
			$timestamp = $this->convertTimestamp($timestamp, $this->getTimeZone(), $setup["fromTimeZone"]);

		if ($setup["format"] ?? false)
			return date($setup["format"], $timestamp);

		switch ($setup["style"]) {
			case \Cherrycake\TIMESTAMP_FORMAT_BASIC:

				if ($setup["isDay"]) {
					$isCurrentYear = (date("Y", $timestamp) == date("Y"));

					switch ($this->locale["dateFormat"]) {
						case \Cherrycake\DATE_FORMAT_LITTLE_ENDIAN:
							$dateFormat = "j/n".((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? "/".($setup["isShortYear"] ? "y" : "Y") : "");
							break;
						case \Cherrycake\DATE_FORMAT_BIG_ENDIAN:
							$dateFormat = ((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? ($setup["isShortYear"] ? "y" : "Y")."/" : "")."n/j";
							break;
						case \Cherrycake\DATE_FORMAT_MIDDLE_ENDIAN:
							$dateFormat = "n/j".((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? "/".($setup["isShortYear"] ? "y" : "Y") : "");
							break;
					}
				}

				if ($setup["isHours"]) {
					if ($setup["hoursFormat"] == \Cherrycake\HOURS_FORMAT_12H)
						$dateFormat .= " h:i".($setup["isSeconds"] ? ".s" : "")." a";
					else
					if ($setup["hoursFormat"] == \Cherrycake\HOURS_FORMAT_24H)
						$dateFormat .= " H:i".($setup["isSeconds"] ? ".s" : "");
				}

				$r = date($dateFormat, $timestamp);

				break;
			
			case \Cherrycake\TIMESTAMP_FORMAT_HUMAN:

				if ($setup["isDay"]) {
					$isCurrentYear = (date("Y", $timestamp) == date("Y"));

					switch ($this->locale["dateFormat"]) {
						case \Cherrycake\DATE_FORMAT_LITTLE_ENDIAN:
							$r =
								date("j", $timestamp).
								($setup["isBrief"] ? " " : " ".$this->getFromArray($this->texts["prepositionOf"], $setup["language"])." ").
								$this->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"])[date("n", $timestamp) - 1].
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									($setup["isBrief"] ? " " : " ".$this->getFromArray($this->texts["prepositionOf"], $setup["language"])." ").
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp)
								: null);
							break;
						case \Cherrycake\DATE_FORMAT_BIG_ENDIAN:
							$r =
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp).
									" "
								: null).
								$this->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"])[date("n", $timestamp) - 1].
								" ".
								date("j", $timestamp);
								
							break;
						case \Cherrycake\DATE_FORMAT_MIDDLE_ENDIAN:
							$r =
								$this->getFromArray($this->texts[($setup["isBrief"] ?? false ? "monthsShort" : "monthsLong")], $setup["language"] ?? false)[date("n", $timestamp) - 1].
								" ".
								$this->getAbbreviatedOrdinal(date("j", $timestamp), ["language" => $setup["language"] ?? false, "ordinalGender" => ORDINAL_GENDER_MALE]).
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									", ".
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp)
								: null);
							break;
					}
				}

				if ($setup["isHours"]) {
					$r .=
						($setup["isBrief"] ? " " : " ".$this->getFromArray($this->texts["prepositionAt"], $setup["language"])." ");

					if ($setup["hoursFormat"] == \Cherrycake\HOURS_FORMAT_12H)
						$r .= date(" h:i".($setup["isSeconds"] ? ".s" : "")." a", $timestamp);
					else
					if ($setup["hoursFormat"] == \Cherrycake\HOURS_FORMAT_24H)
						$r .= date(" H:i".($setup["isSeconds"] ? ".s" : ""), $timestamp);
				}

				break;

			case \Cherrycake\TIMESTAMP_FORMAT_RELATIVE_HUMAN:
				// If in the past
				if ($timestamp < time()) {

					// Check is yesterday
					if (mktime(0, 0, 0, date("n", $timestamp), date("j", $timestamp), date("Y", $timestamp)) == mktime(0, 0, 0, date("n"), date("j")-1, date("Y"))) {
						$r = $this->getFromArray($this->texts["yesterday"], $setup["language"]);
						break;
					}

					$minutesAgo = floor((time() - $timestamp) / 60);

					if ($minutesAgo < 5) {
						$r = $this->getFromArray($this->texts["justNow"], $setup["language"]);
						break;
					}

					if ($minutesAgo < 60) {
						$r =
							$this->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$minutesAgo.
							" ".
							($minutesAgo == 1 ? $this->getFromArray($this->texts["minute"], $setup["language"]) : $this->getFromArray($this->texts["minutes"], $setup["language"])).
							" ".
							$this->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

					$hoursAgo = floor($minutesAgo / 60);

					if ($hoursAgo < 24) {
						$r =
							$this->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? false).
							$hoursAgo.
							" ".
							($hoursAgo == 1 ? $this->getFromArray($this->texts["hour"], $setup["language"] ?? false) : $this->getFromArray($this->texts["hours"], $setup["language"] ?? false)).
							" ".
							$this->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? false);
						break;
					}

					$daysAgo = floor($hoursAgo / 24);

					if ($daysAgo < 30) {
						$r =
							$this->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$daysAgo.
							" ".
							($daysAgo == 1 ? $this->getFromArray($this->texts["day"], $setup["language"]) : $this->getFromArray($this->texts["days"], $setup["language"])).
							" ".
							$this->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

					$monthsAgo = date("Ym")-date("Ym", $timestamp);

					if ($monthsAgo < 4) {
						$r =
							$this->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$monthsAgo.
							" ".
							($monthsAgo == 1 ? $this->getFromArray($this->texts["month"], $setup["language"]) : $this->getFromArray($this->texts["months"], $setup["language"])).
							" ".
							$this->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

				}

				// Other cases: Future timestamps, and timestamps not handled by the humanizer above
				$monthNames = $this->getFromArray($this->texts["monthsLong"], $setup["language"] ?? false);
				$r =
					$monthNames[date("n", $timestamp)-1].
					" ".
					date("Y", $timestamp);

				break;
		}

		return $r;
	}

	/**
	 * Formats the given number
	 *
	 * @param float $timestamp The number
	 * @param array $setup An optional hash array with setup options, with the following possible keys:
	 * * decimals: The number of decimals to show. Default: 0
	 * * showDecimalsForWholeNumbers: Whether to show the decimal part when the number is whole. Default: false
	 * * decimalMark: The decimal mark to use, DECIMAL_MARK_POINT or DECIMAL_MARK_COMMA. Defaults to the current Locale setting.
	 * * isSeparateThousands: Whether to separate thousands or not. Default: false
	 * * multiplier: A multiplier, or false if no multiplier should be applied. Default: false
	 * @return string The formatted number.
	 */
	function formatNumber($number, $setup = false) {
		self::treatParameters($setup, [
            "decimals" => ["default" => 0],
			"showDecimalsForWholeNumbers" => ["default" => false],
			"decimalMark" => ["default" => $this->locale["decimalMark"]],
			"isSeparateThousands" => ["default" => false]
        ]);

		if ($setup["multiplier"] ?? false)
			$number *= $setup["multiplier"];

		return number_format(
			$number,
			(round($number) == $number && $setup["showDecimalsForWholeNumbers"]) || round($number) != $number ? $setup["decimals"] : 0,
			[DECIMAL_MARK_POINT => ".", DECIMAL_MARK_COMMA => ","][$setup["decimalMark"]],
			$setup["isSeparateThousands"] ? [DECIMAL_MARK_POINT => ",", DECIMAL_MARK_COMMA => "."][$setup["decimalMark"]] : false
		);
	}

	/**
	 * Formats the given amount as a currency
	 * 
	 * @param float $amount
	 * @param array $setup An optional hash array with setup options, with the following possible keys:
	 * * currency: The currency to format the given amount to. One of the available CURRENCY_?. If not specified, the current Locale setting is used.
	 */
	function formatCurrency($amount, $setup = false) {
		switch ($this->getCurrency()) {
			case CURRENCY_USD:
				return "USD".$this->formatNumber($amount, [
					"isSeparateThousands" => true,
					"decimals" => 2
				]);
				break;
			case CURRENCY_EURO:
				return $this->formatNumber($amount, [
					"isSeparateThousands" => true,
					"decimals" => 2
				])."€";
				break;
		}
	}

	/**
	 * @param integer $number The number
	 * @param array $setup A hash array of setup options with the following possible keys
	 * * forceLanguage: default: false. If specified, this language will be used instead of the detected one.
	 * * ordinalGender: default: ORDINAL_GENDER_MALE. Some languages have different ordinals depending on the gender of what's being counted. Specify this gender here, one of the ORDINAL_GENDER_* available ones.
	 * @return string The abbreviated ordinal number string corresponding to the given number
	 */
	function getAbbreviatedOrdinal($number, $setup = false) {
		if (!$setup["language"])
			$setup["language"] = $this->getLanguage();

		switch ($setup["language"]) {
			case LANGUAGE_ENGLISH:
				$r = $number;
				switch($number) {
					case 1:
					case 21:
					case 31:
						$r .= "st";
						break;
					case 2:
					case 22:
						$r .= "nd";
						break;
					default:
						$r .= "th";
						break;
				}
				break;

			case LANGUAGE_SPANISH:
				$r = $number."º";
				break;
		}

		return $r;
	}

	/**
	 * This method tries to detect the user's location using the configured geolocationMethod. If contry-only methods like GEOLOCATION_METHOD_CLOUDFLARE are configured, only the country will be set in the returned Location object.
	 * @return mixed A Location object specifying the user's location, or false if it could not be determined.
	 */
	function guessLocation() {
		switch ($this->getConfig("geolocationMethod")) {
			case GEOLOCATION_METHOD_CLOUDFLARE:
				if (!isset($_SERVER["HTTP_CF_IPCOUNTRY"]))
					return false;
				$location = new \Cherrycake\Location;
				if (!$location->loadCountryFromCode($_SERVER["HTTP_CF_IPCOUNTRY"]))
					return false;
				return $location;
			default:
				return false;
		}
	}
}