<?php

namespace Cherrycake\Locale;

/**
 * The Locale module provides localization functionalities for multilingual web sites with automatic detection, plus the handling of currencies, dates, timezones and more.
 *
 * @package Cherrycake
 * @category Modules
 */
class Locale extends \Cherrycake\Module {
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		/*
			A hash array of available localizations the app supports, where each key is the locale name, and each value a hash array with the following keys:
				domains: An array of domains that will trigger this localization when the request to the app comes from one of them, or false if this is the only locale to be used always.
				language: The language used in this localization, one of the available \Cherrycake\LANGUAGE_? constants.
				dateFormat: The date format used in this localization, one of the available \Cherrycake\DATE_FORMAT_? constants.
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
				"language" => \Cherrycake\LANGUAGE_ENGLISH,
				"dateFormat" => \Cherrycake\DATE_FORMAT_MIDDLE_ENDIAN,
				"temperatureUnits" => \Cherrycake\TEMPERATURE_UNITS_FAHRENHEIT,
				"currency" => \Cherrycake\CURRENCY_USD,
				"decimalMark" => \Cherrycake\DECIMAL_MARK_POINT,
				"measurementSystem" => \Cherrycake\MEASUREMENT_SYSTEM_IMPERIAL,
				"timeZone" => \Cherrycake\TIMEZONE_ID_ETC_UTC
			]
		],
		"defaultLocale" => "main", // The locale name to use when it can not be autodetected.
		"canonicalLocale" => false, // The locale to consider canonical, used i.e. in the HtmlDocument module to set the rel="canonical" meta tag, in order to let search engines understand that there are different pages in different languages that represent the same content.
		"geolocationMethod" => \Cherrycake\GEOLOCATION_METHOD_CLOUDFLARE, // The method to use to determine the user's geographical location, one of the available LOCALE_GEOLOCATION_METHOD_? constants.
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
		\Cherrycake\LANGUAGE_SPANISH => [
			\Cherrycake\LANGUAGE_SPANISH => "Español",
			\Cherrycake\LANGUAGE_ENGLISH => "Spanish",
			\Cherrycake\LANGUAGE_CATALAN => "Espanyol",
			\Cherrycake\LANGUAGE_FRENCH => "Espagnol"
		],
		\Cherrycake\LANGUAGE_ENGLISH => [
			\Cherrycake\LANGUAGE_SPANISH => "Inglés",
			\Cherrycake\LANGUAGE_ENGLISH => "English",
			\Cherrycake\LANGUAGE_CATALAN => "Anglès",
			\Cherrycake\LANGUAGE_FRENCH => "Anglaise"
		],
		\Cherrycake\LANGUAGE_CATALAN => [
			\Cherrycake\LANGUAGE_SPANISH => "Catalán",
			\Cherrycake\LANGUAGE_ENGLISH => "Catalan",
			\Cherrycake\LANGUAGE_CATALAN => "Català",
			\Cherrycake\LANGUAGE_FRENCH => "Catalan"
		],
		\Cherrycake\LANGUAGE_FRENCH => [
			\Cherrycake\LANGUAGE_SPANISH => "Francés",
			\Cherrycake\LANGUAGE_ENGLISH => "French",
			\Cherrycake\LANGUAGE_CATALAN => "Francès",
			\Cherrycake\LANGUAGE_FRENCH => "Français"
		]
	];

	/**
	 * @var array $languageCodes A hash array of ISO 639-1 language codes
	 */
	private $languageCodes = [
		\Cherrycake\LANGUAGE_SPANISH => "es",
		\Cherrycake\LANGUAGE_ENGLISH => "en",
		\Cherrycake\LANGUAGE_CATALAN => "cat",
		\Cherrycake\LANGUAGE_FRENCH => "fr"
	];

	/**
	 * @var array $texts A hash array with some common texts used by this module
	 */
	private $texts = [
		"justNow" => [
			\Cherrycake\LANGUAGE_SPANISH => "justo ahora",
			\Cherrycake\LANGUAGE_ENGLISH => "just now",
			\Cherrycake\LANGUAGE_CATALAN => "just ara",
			\Cherrycake\LANGUAGE_FRENCH => "juste maintenant"
		],
		"agoPrefix" => [
			\Cherrycake\LANGUAGE_SPANISH => "hace ",
			\Cherrycake\LANGUAGE_CATALAN => "fa ",
			\Cherrycake\LANGUAGE_FRENCH => "il y a "
		],
		"agoSuffix" => [
			\Cherrycake\LANGUAGE_ENGLISH => " ago"
		],
		"minute" => [
			\Cherrycake\LANGUAGE_SPANISH => "minuto",
			\Cherrycake\LANGUAGE_ENGLISH => "minute",
			\Cherrycake\LANGUAGE_CATALAN => "minut",
			\Cherrycake\LANGUAGE_FRENCH => "minute"
		],
		"minutes" => [
			\Cherrycake\LANGUAGE_SPANISH => "minutos",
			\Cherrycake\LANGUAGE_ENGLISH => "minutes",
			\Cherrycake\LANGUAGE_CATALAN => "minuts",
			\Cherrycake\LANGUAGE_FRENCH => "minutes"
		],
		"hour" => [
			\Cherrycake\LANGUAGE_SPANISH => "hora",
			\Cherrycake\LANGUAGE_ENGLISH => "hour"
		],
		"hours" => [
			\Cherrycake\LANGUAGE_SPANISH => "horas",
			\Cherrycake\LANGUAGE_ENGLISH => "hours",
			\Cherrycake\LANGUAGE_CATALAN => "hores",
			\Cherrycake\LANGUAGE_FRENCH => "heures"
		],
		"day" => [
			\Cherrycake\LANGUAGE_SPANISH => "día",
			\Cherrycake\LANGUAGE_ENGLISH => "day",
			\Cherrycake\LANGUAGE_CATALAN => "dia",
			\Cherrycake\LANGUAGE_FRENCH => "journée"
		],
		"days" => [
			\Cherrycake\LANGUAGE_SPANISH => "días",
			\Cherrycake\LANGUAGE_ENGLISH => "days",
			\Cherrycake\LANGUAGE_CATALAN => "dies",
			\Cherrycake\LANGUAGE_FRENCH => "jours"
		],
		"month" => [
			\Cherrycake\LANGUAGE_SPANISH => "mes",
			\Cherrycake\LANGUAGE_ENGLISH => "month",
			\Cherrycake\LANGUAGE_CATALAN => "mes",
			\Cherrycake\LANGUAGE_FRENCH => "mois"
		],
		"months" => [
			\Cherrycake\LANGUAGE_SPANISH => "meses",
			\Cherrycake\LANGUAGE_ENGLISH => "months",
			\Cherrycake\LANGUAGE_CATALAN => "mesos",
			\Cherrycake\LANGUAGE_FRENCH => "mois"
		],
		"yesterday" => [
			\Cherrycake\LANGUAGE_SPANISH => "ayer",
			\Cherrycake\LANGUAGE_ENGLISH => "yesterday",
			\Cherrycake\LANGUAGE_CATALAN => "ahir",
			\Cherrycake\LANGUAGE_FRENCH => "hier"
		],
		"monthsLong" => [
			\Cherrycake\LANGUAGE_SPANISH => ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"],
			\Cherrycake\LANGUAGE_ENGLISH => ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"],
			\Cherrycake\LANGUAGE_CATALAN => ["gener", "febrer", "març", "abril", "maig", "juny", "juliol", "agost", "setembre", "octubre", "novembre", "desembre"],
			\Cherrycake\LANGUAGE_FRENCH => ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"]
		],
		"monthsShort" => [
			\Cherrycake\LANGUAGE_SPANISH => ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"],
			\Cherrycake\LANGUAGE_ENGLISH => ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"],
			\Cherrycake\LANGUAGE_CATALAN => ["gen", "feb", "mar", "abr", "mai", "jun", "jul", "ago", "set", "oct", "nov", "des"],
			\Cherrycake\LANGUAGE_FRENCH => ["jan", "fév", "mar", "avr", "mai", "jun", "jul", "aoû", "sep", "oct", "nov", "déc"]
		],
		"prepositionOf" => [
			\Cherrycake\LANGUAGE_SPANISH => "de",
			\Cherrycake\LANGUAGE_ENGLISH => "of",
			\Cherrycake\LANGUAGE_CATALAN => "de",
			\Cherrycake\LANGUAGE_FRENCH => "sur"
		],
		"prepositionAt" => [
			\Cherrycake\LANGUAGE_SPANISH => "a las",
			\Cherrycake\LANGUAGE_ENGLISH => "at",
			\Cherrycake\LANGUAGE_CATALAN => "a les",
			\Cherrycake\LANGUAGE_FRENCH => "à"
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

		if (isset($_SERVER["HTTP_HOST"])) {
			foreach ($this->getConfig("availableLocales") as $localeName => $locale) {
				if (($locale["domains"] ?? false) && in_array($_SERVER["HTTP_HOST"], $locale["domains"])) {
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
	 * Gets the main domain name for the current locale, or for the specified locale
	 * @param string $localeName The name of the locale for which to get the main domain
	 * @return string The main domain for the specified locale, or for the current locale if no $locale specified. False if the locale was not found.
	 */
	function getMainDomain($localeName = false) {
		if (!isset($this->getConfig("availableLocales")[$localeName]))
			return false;
		if (
			!isset($this->getConfig("availableLocales")[$localeName]["domains"])
			||
			!is_array($this->getConfig("availableLocales")[$localeName]["domains"])
		)
			return $_SERVER["HTTP_HOST"];
		return $this->getConfig("availableLocales")[$localeName]["domains"][0];
	}

	/**
	 * Gets the languages that are available on the App, taken from the configured `availableLocales`
	 * @return array The languages available
	 */
	function getAvailaleLanguages() {
		$languages = [];
		foreach ($this->getConfig("availableLocales") as $locale)
			$languages[] = $locale['language'];
		return $languages;
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
	 * Sets the date format to use
	 * @param integer $dateFormat The desired dateFormat, one of the available \Cherrycake\DATE_FORMAT_*
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
	 * @return integer The language that is being currently used, one of the \Cherrycake\LANGUAGE_*
	 */
	function getLanguage() {
		return $this->locale["language"];
	}

	/**
	 * @return integer The language that is being currently used, one of the \Cherrycake\LANGUAGE_*
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
	 * * language: If specified, this language will be used instead of the detected one. One of the available \Cherrycake\LANGUAGE_?
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
								($setup["isBrief"] ? " " : " ".$e->Language->getFromArray($this->texts["prepositionOf"], $setup["language"])." ").
								$e->Language->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"])[date("n", $timestamp) - 1].
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									($setup["isBrief"] ? " " : " ".$e->Language->getFromArray($this->texts["prepositionOf"], $setup["language"])." ").
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp)
								: null);
							break;
						case \Cherrycake\DATE_FORMAT_BIG_ENDIAN:
							$r =
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp).
									" "
								: null).
								$e->Language->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"])[date("n", $timestamp) - 1].
								" ".
								date("j", $timestamp);

							break;
						case \Cherrycake\DATE_FORMAT_MIDDLE_ENDIAN:
							$r =
								$e->Language->getFromArray($this->texts[($setup["isBrief"] ?? false ? "monthsShort" : "monthsLong")], $setup["language"] ?? false)[date("n", $timestamp) - 1].
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
						($setup["isBrief"] ? " " : " ".$e->Language->getFromArray($this->texts["prepositionAt"], $setup["language"])." ");

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
						$r = $e->Language->getFromArray($this->texts["yesterday"], $setup["language"]);
						break;
					}

					$minutesAgo = floor((time() - $timestamp) / 60);

					if ($minutesAgo < 5) {
						$r = $e->Language->getFromArray($this->texts["justNow"], $setup["language"]);
						break;
					}

					if ($minutesAgo < 60) {
						$r =
							$e->Language->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$minutesAgo.
							" ".
							($minutesAgo == 1 ? $e->Language->getFromArray($this->texts["minute"], $setup["language"]) : $e->Language->getFromArray($this->texts["minutes"], $setup["language"])).
							" ".
							$e->Language->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

					$hoursAgo = floor($minutesAgo / 60);

					if ($hoursAgo < 24) {
						$r =
							$e->Language->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? false).
							$hoursAgo.
							" ".
							($hoursAgo == 1 ? $e->Language->getFromArray($this->texts["hour"], $setup["language"] ?? false) : $e->Language->getFromArray($this->texts["hours"], $setup["language"] ?? false)).
							" ".
							$e->Language->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? false);
						break;
					}

					$daysAgo = floor($hoursAgo / 24);

					if ($daysAgo < 30) {
						$r =
							$e->Language->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$daysAgo.
							" ".
							($daysAgo == 1 ? $e->Language->getFromArray($this->texts["day"], $setup["language"]) : $e->Language->getFromArray($this->texts["days"], $setup["language"])).
							" ".
							$e->Language->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

					$monthsAgo = date("Ym")-date("Ym", $timestamp);

					if ($monthsAgo < 4) {
						$r =
							$e->Language->getFromArray($this->texts["agoPrefix"], $setup["language"]).
							$monthsAgo.
							" ".
							($monthsAgo == 1 ? $e->Language->getFromArray($this->texts["month"], $setup["language"]) : $e->Language->getFromArray($this->texts["months"], $setup["language"])).
							" ".
							$e->Language->getFromArray($this->texts["agoSuffix"], $setup["language"]);
						break;
					}

				}

				// Other cases: Future timestamps, and timestamps not handled by the humanizer above
				$monthNames = $e->Language->getFromArray($this->texts["monthsLong"], $setup["language"] ?? false);
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
			[\Cherrycake\DECIMAL_MARK_POINT => ".", \Cherrycake\DECIMAL_MARK_COMMA => ","][$setup["decimalMark"]],
			$setup["isSeparateThousands"] ? [\Cherrycake\DECIMAL_MARK_POINT => ",", \Cherrycake\DECIMAL_MARK_COMMA => "."][$setup["decimalMark"]] : false
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
			case \Cherrycake\CURRENCY_USD:
				return "USD".$this->formatNumber($amount, [
					"isSeparateThousands" => true,
					"decimals" => 2
				]);
				break;
			case \Cherrycake\CURRENCY_EURO:
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
			case \Cherrycake\LANGUAGE_ENGLISH:
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

			case \Cherrycake\LANGUAGE_SPANISH:
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
