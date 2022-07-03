<?php

namespace Cherrycake\Modules\Locale;

use Cherrycake\Classes\Engine;
use Cherrycake\Modules\Cache\Cache;

/**
 * The Locale module provides localization functionalities for multilingual web sites with automatic detection, plus the handling of currencies, dates, timezones and more.
 */
class Locale extends \Cherrycake\Classes\Module {

	const SPANISH = 1;
	const ENGLISH = 2;
	const CATALAN = 3;
	const FRENCH = 4;

	const DATE_FORMAT_LITTLE_ENDIAN = 1;  // Almost all the world, like "20/12/2010", "9 November 2003", "Sunday, 9 November 2003", "9 November 2003"
	const DATE_FORMAT_BIG_ENDIAN = 2; // Asian countries, Hungary and Sweden, like "2010/12/20", "2003 November 9", "2003-Nov-9, Sunday"
	const DATE_FORMAT_MIDDLE_ENDIAN = 3; // United states and Canada, like "12/20/2010", "Sunday, November 9, 2003", "November 9, 2003", "Nov. 9, 2003", "Nov/9/2003"

	const TEMPERATURE_UNITS_FAHRENHEIT = 1;
	const TEMPERATURE_UNITS_CELSIUS = 2;

	const CURRENCY_USD = 1;
	const CURRENCY_EURO = 2;

	const DECIMAL_MARK_POINT = 0;
	const DECIMAL_MARK_COMMA = 1;

	const MEASUREMENT_SYSTEM_IMPERIAL = 1;
	const MEASUREMENT_SYSTEM_METRIC = 2;

	const HOURS_FORMAT_12H = 1;
	const HOURS_FORMAT_24H = 2;

	const TIMESTAMP_FORMAT_BASIC = 0; // Basic formatting, like "5/18/2020"
	const TIMESTAMP_FORMAT_HUMAN = 1; // Human readable formatting, like "may 18th, 2020"
	const TIMESTAMP_FORMAT_RELATIVE_HUMAN = 2; // Formatting relative to now, like "10 hours ago"

	const ORDINAL_GENDER_MALE = 0;
	const ORDINAL_GENDER_FEMALE = 1;

	const GEOLOCATION_METHOD_CLOUDFLARE = 0;

	const TIMEZONE_ID_ETC_UTC = 532; // The id of the timezone in the cherrycake_location_timezones
	const TIMEZONE_ID_EUROPE_MADRID = 390;

	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [
		/*
			A hash array of available localizations the app supports, where each key is the locale name, and each value a hash array with the following keys:
				domains: An array of domains that will trigger this localization when the request to the app comes from one of them, or false if this is the only locale to be used always.
				isHttps: A boolean indicating whether requests to the locale should be done via HTTPS.
				language: The language used in this localization, one of the available \Cherrycake\Modules\Locale\Locale::? constants.
				dateFormat: The date format used in this localization, one of the available \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_? constants.
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
				"isHttps" => true,
				"language" => \Cherrycake\Modules\Locale\Locale::ENGLISH,
				"dateFormat" => \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_MIDDLE_ENDIAN,
				"temperatureUnits" => \Cherrycake\Modules\Locale\Locale::TEMPERATURE_UNITS_FAHRENHEIT,
				"currency" => \Cherrycake\Modules\Locale\Locale::CURRENCY_USD,
				"decimalMark" => \Cherrycake\Modules\Locale\Locale::DECIMAL_MARK_POINT,
				"measurementSystem" => \Cherrycake\Modules\Locale\Locale::MEASUREMENT_SYSTEM_IMPERIAL,
				"timeZone" => \Cherrycake\Modules\Locale\Locale::TIMEZONE_ID_ETC_UTC
			]
		],
		"defaultLocale" => "main", // The locale name to use when it can not be autodetected.
		"canonicalLocale" => false, // The locale to consider canonical, used i.e. in the HtmlDocument module to set the rel="canonical" meta tag, in order to let search engines understand that there are different pages in different languages that represent the same content.
		"geolocationMethod" => \Cherrycake\Modules\Locale\Locale::GEOLOCATION_METHOD_CLOUDFLARE, // The method to use to determine the user's geographical location, one of the available LOCALE_GEOLOCATION_METHOD_? constants.
		"timeZonesDatabaseProviderName" => "main", // The name of the database provider where the timezones are found
		"timeZonesTableName" => "cherrycake_location_timezones", // The name of the table where the timezones are stored. See the cherrycake_location_timezones table in the Cherrycake skeleton database.
		"timeZonesCacheProviderName" => "engine", // The name of the cache provider that will be user to cache timezones
		"timeZonesCacheKeyPrefix" => "LocaleTimeZone", // The prefix of the keys when storing timezones into cache
		"timeZonesCacheDefaultTtl" => Cache::TTL_NORMAL // The default TTL for timezones stored into cache
	];

	/**
	 * @var array $dependentCoreModules Core module names that are required by this module
	 */
	protected array $dependentCoreModules = [
		"Output",
		"Errors",
		"Cache",
		"Database"
	];

	/**
	 * @var array $locale The current locale settings
	 */
	public $locale;

	/**
	 * @var string $localeName The name of the current locale
	 */
	private $localeName;

	private $languageNames = [
		\Cherrycake\Modules\Locale\Locale::SPANISH => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "Español",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "Spanish",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "Espanyol",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "Espagnol"
		],
		\Cherrycake\Modules\Locale\Locale::ENGLISH => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "Inglés",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "English",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "Anglès",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "Anglaise"
		],
		\Cherrycake\Modules\Locale\Locale::CATALAN => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "Catalán",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "Catalan",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "Català",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "Catalan"
		],
		\Cherrycake\Modules\Locale\Locale::FRENCH => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "Francés",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "French",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "Francès",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "Français"
		]
	];

	/**
	 * @var array $languageCodes A hash array of ISO 639-1 language codes
	 */
	private $languageCodes = [
		\Cherrycake\Modules\Locale\Locale::SPANISH => "es",
		\Cherrycake\Modules\Locale\Locale::ENGLISH => "en",
		\Cherrycake\Modules\Locale\Locale::CATALAN => "cat",
		\Cherrycake\Modules\Locale\Locale::FRENCH => "fr"
	];

	/**
	 * @var array $texts A hash array with some common texts used by this module
	 */
	private $texts = [
		"justNow" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "justo ahora",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "just now",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "just ara",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "juste maintenant"
		],
		"agoPrefix" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "hace ",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "fa ",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "il y a "
		],
		"agoSuffix" => [
			\Cherrycake\Modules\Locale\Locale::ENGLISH => " ago"
		],
		"minute" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "minuto",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "minute",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "minut",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "minute"
		],
		"minutes" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "minutos",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "minutes",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "minuts",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "minutes"
		],
		"hour" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "hora",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "hour"
		],
		"hours" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "horas",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "hours",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "hores",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "heures"
		],
		"day" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "día",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "day",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "dia",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "journée"
		],
		"days" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "días",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "days",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "dies",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "jours"
		],
		"month" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "mes",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "month",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "mes",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "mois"
		],
		"months" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "meses",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "months",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "mesos",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "mois"
		],
		"yesterday" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "ayer",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "yesterday",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "ahir",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "hier"
		],
		"monthsLong" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"],
			\Cherrycake\Modules\Locale\Locale::ENGLISH => ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"],
			\Cherrycake\Modules\Locale\Locale::CATALAN => ["gener", "febrer", "març", "abril", "maig", "juny", "juliol", "agost", "setembre", "octubre", "novembre", "desembre"],
			\Cherrycake\Modules\Locale\Locale::FRENCH => ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"]
		],
		"monthsShort" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"],
			\Cherrycake\Modules\Locale\Locale::ENGLISH => ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"],
			\Cherrycake\Modules\Locale\Locale::CATALAN => ["gen", "feb", "mar", "abr", "mai", "jun", "jul", "ago", "set", "oct", "nov", "des"],
			\Cherrycake\Modules\Locale\Locale::FRENCH => ["jan", "fév", "mar", "avr", "mai", "jun", "jul", "aoû", "sep", "oct", "nov", "déc"]
		],
		"prepositionOf" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "de",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "of",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "de",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "sur"
		],
		"prepositionAt" => [
			\Cherrycake\Modules\Locale\Locale::SPANISH => "a las",
			\Cherrycake\Modules\Locale\Locale::ENGLISH => "at",
			\Cherrycake\Modules\Locale\Locale::CATALAN => "a les",
			\Cherrycake\Modules\Locale\Locale::FRENCH => "à"
		]
	];

	/**
	 * Initializes the module. Detects and assigns the locale depending on the requested domain.
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
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
	 * Gets the name of the current locale
	 * @return string The name of the current locale, as specified in the availableLocales config key.
	 */
	public function getLocaleName() {
		return $this->localeName;
	}

	/**
	 * Sets the locale to use
	 * @param string $localeName The name of the locale to use, as specified in the availableLocales config key.
	 * @return boolean True if the locale could be set, false if the locale wasn't configured in the availableLocales config key.
	 */
	public function setLocale($localeName) {
		if (!isset($this->getConfig("availableLocales")[$localeName]))
			return false;
		$this->locale = $this->getConfig("availableLocales")[$localeName];
		$this->localeName = $localeName;
		return true;
	}

	/**
	 * Gets the main domain name for the current locale, or for the specified locale
	 * @param string $localeName The name of the locale for which to get the main domain
	 * @return string The main domain for the specified locale, or for the current locale if no $locale specified. False if the locale was not found.
	 */
	public function getMainDomain($localeName = false) {
		if (!$localeName)
			$localeName = $this->getLocaleName();
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
	 * Gets the base URL for the current locale, or for the specified locale
	 * @param string $localeName The name of the locale for which to get the main domain
	 * @return string The base URL
	 */
	public function getBaseUrl($localeName = false) {
		if (!$localeName)
			$localeName = $this->getLocaleName();
		return
			(
				$this->getConfig('availableLocales')[$localeName]['isHttps'] ?? $_SERVER["HTTPS"]
				?
				'https://'
				:
				'http://'
			).
			$this->getMainDomain($localeName);
	}

	/**
	 * Gets the languages that are available on the App, taken from the configured `availableLocales`
	 * @return array The languages available
	 */
	public function getAvailaleLanguages() {
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
	public function getLanguageName($language, $setup = false) {
		if (!isset($this->languageNames[$language]))
			return false;
		return $this->languageNames[$language][$setup["forceLanguage"] ?? false ?: $this->getLanguage()];
	}

	/**
	 * Gets the code of a language
	 * @param integer $language The language
	 * @return mixed The language code, or false if the specified language is not configured.
	 */
	public function getLanguageCode($language = false) {
		if (!$language)
			$language = $this->getLanguage();
		if (!isset($this->languageCodes[$language]))
			return false;
		return $this->languageCodes[$language];
	}

	/**
	 * Sets the date format to use
	 * @param integer $dateFormat The desired dateFormat, one of the available \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_*
	 */
	public function setDateFormat($dateFormat) {
		$this->locale["dateFormat"] = $dateFormat;
	}

	/**
	 * Sets the temperature units to use
	 * @param integer $temperatureUnits The desired temperature units, one of the available TEMPERATURE_UNITS_*
	 */
	public function setTemperatureUnits($temperatureUnits) {
		$this->locale["temperatureUnits"] = $temperatureUnits;
	}

	/**
	 * Sets the currency to use
	 * @param integer $currency The desired currency, one of the available CURRENCY_*
	 */
	public function setCurrency($currency) {
		$this->locale["currency"] = $currency;
	}

	/**
	 * Sets the decimal mark to use
	 * @param integer $decimalMark The desired decimal mark, one of the available DECIMAL_MARK_*
	 */
	public function setDecimalMark($decimalMark) {
		$this->locale["decimalMark"] = $decimalMark;
	}

	/**
	 * Sets the measurement system to use
	 * @param integer $measurementSystem The desired measurement system, one of the available MEASUREMENT_SYSTEM_*
	 */
	public function setMeasurementSystem($measurementSystem) {
		$this->locale["measurementSystem"] = $measurementSystem;
	}

	/**
	 * Sets the language to use
	 * @param integer $language The language
	 */
	public function setLanguage($language) {
		$this->locale["language"] = $language;
	}

	/**
	 * @return integer The language that is being currently used, one of the \Cherrycake\Modules\Locale\Locale::*
	 */
	public function getLanguage() {
		return $this->locale["language"];
	}

	/**
	 * @return integer The language that is being currently used, one of the \Cherrycake\Modules\Locale\Locale::*
	 */
	public function getCurrency() {
		return $this->locale["currency"];
	}

	/**
	 * Sets the Timezone to use
	 * @param integer $timeZone The desired timezone, one of defined in PHP constants as specified in http://php.net/manual/en/timezones.php
	 */
	public function setTimeZone($timeZone) {
		$this->locale["timeZone"] = $timeZone;
	}

	/**
	 * @return integer The timezone being used
	 */
	public function getTimeZone() {
		return $this->locale["timeZone"];
	}

	/**
	 * @param integer $timezone The timezone id to obtain the name of. If not specified, the current locale timezone is used
	 * @return string The timezone name in the TZ standard
	 */
	public function getTimeZoneName($timezone = false) {
		if (!$timezone)
			$timezone = $this->getTimeZone();

		$cacheKey = Engine::e()->Cache->buildCacheKey(
			prefix: $this->getConfig("timeZonesCacheKeyPrefix"),
			uniqueId: $timezone
		);
		$cacheProviderName = $this->getConfig("timeZonesCacheProviderName");

		if (!$timeZoneName = Engine::e()->Cache->$cacheProviderName->get($cacheKey)) { // Get the timezone name from the cache
			// If not in the cache, retrieve it from the DB
			$databaseProviderName = $this->getConfig("textsDatabaseProviderName");

			$result = Engine::e()->Database->$databaseProviderName->query("select timezone as timeZoneName from ".$this->getConfig("timeZonesTableName")." where id = ".Engine::e()->Database->$databaseProviderName->safeString($timezone));
			if (!$result->isAny()) {
				Engine::e()->Errors->trigger(
					type: Errors::ERROR_SYSTEM,
					description: "Requested timezone not found",
					variables: ["timezone" => $timezone],
					isSilent: true
				);
				return Engine::e()->getTimezoneName();
			}

			$row = $result->getRow();
			$timeZoneName = $row->getField("timeZoneName");

			// Store in cache
			Engine::e()->Cache->$cacheProviderName->set($cacheKey, $timeZoneName, $this->getConfig("timeZonesCacheDefaultTtl"));
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
	public function convertTimestamp($timestamp, $toTimeZone = false, $fromTimeZone = false) {
		if (!$timestamp)
			return false;

		if (!$fromTimeZone) {
			$fromTimeZone = Engine::e()->getTimezoneId();
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
	public function formatDate($dateTimestamp, $setup = false) {
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
	 * * language: If specified, this language will be used instead of the detected one. One of the available \Cherrycake\Modules\Locale\Locale::?
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
	public function formatTimestamp($timestamp, $setup = false) {
		// If no fromTimeZone specified for the given timestamp, the engine TIMEZONE is assumed
		if (!isset($setup["fromTimeZone"])) {
			$setup["fromTimeZone"] = Engine::e()->getTimezoneId();
		}

		if (!isset($setup["style"]))
			$setup["style"] = \Cherrycake\Modules\Locale\Locale::TIMESTAMP_FORMAT_BASIC;

		if (!isset($setup["isShortYear"]))
			$setup["isShortYear"] = true;

		if (!isset($setup["isDay"]))
			$setup["isDay"] = true;

		if (!isset($setup["isHours"]))
			$setup["isHours"] = false;

		if (!isset($setup["hoursFormat"]))
			$setup["hoursFormat"] = \Cherrycake\Modules\Locale\Locale::HOURS_FORMAT_24H;

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
			case \Cherrycake\Modules\Locale\Locale::TIMESTAMP_FORMAT_BASIC:

				if ($setup["isDay"]) {
					$isCurrentYear = (date("Y", $timestamp) == date("Y"));

					switch ($this->locale["dateFormat"]) {
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_LITTLE_ENDIAN:
							$dateFormat = "j/n".((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? "/".($setup["isShortYear"] ? "y" : "Y") : "");
							break;
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_BIG_ENDIAN:
							$dateFormat = ((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? ($setup["isShortYear"] ? "y" : "Y")."/" : "")."n/j";
							break;
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_MIDDLE_ENDIAN:
							$dateFormat = "n/j".((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ? "/".($setup["isShortYear"] ? "y" : "Y") : "");
							break;
					}
				}

				if ($setup["isHours"]) {
					if ($setup["hoursFormat"] == \Cherrycake\Modules\Locale\Locale::HOURS_FORMAT_12H)
						$dateFormat .= " h:i".($setup["isSeconds"] ? ".s" : "")." a";
					else
					if ($setup["hoursFormat"] == \Cherrycake\Modules\Locale\Locale::HOURS_FORMAT_24H)
						$dateFormat .= " H:i".($setup["isSeconds"] ? ".s" : "");
				}

				$r = date($dateFormat, $timestamp);

				break;

			case \Cherrycake\Modules\Locale\Locale::TIMESTAMP_FORMAT_HUMAN:

				if ($setup["isDay"]) {
					$isCurrentYear = (date("Y", $timestamp) == date("Y"));

					switch ($this->locale["dateFormat"]) {
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_LITTLE_ENDIAN:
							$r =
								date("j", $timestamp).
								($setup["isBrief"] ? " " : " ".Engine::e()->Translation->getFromArray($this->texts["prepositionOf"], $setup["language"] ?? null)." ").
								Engine::e()->Translation->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"] ?? null)[date("n", $timestamp) - 1].
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									($setup["isBrief"] ? " " : " ".Engine::e()->Translation->getFromArray($this->texts["prepositionOf"], $setup["language"] ?? null)." ").
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp)
								: null);
							break;
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_BIG_ENDIAN:
							$r =
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp).
									" "
								: null).
								Engine::e()->Translation->getFromArray($this->texts[($setup["isBrief"] ? "monthsShort" : "monthsLong")], $setup["language"] ?? null)[date("n", $timestamp) - 1].
								" ".
								date("j", $timestamp);

							break;
						case \Cherrycake\Modules\Locale\Locale::DATE_FORMAT_MIDDLE_ENDIAN:
							$r =
								Engine::e()->Translation->getFromArray($this->texts[($setup["isBrief"] ?? false ? "monthsShort" : "monthsLong")], $setup["language"] ?? null ?? false)[date("n", $timestamp) - 1].
								" ".
								$this->getAbbreviatedOrdinal(date("j", $timestamp), ["language" => $setup["language"] ?? null ?? false, "ordinalGender" => ORDINAL_GENDER_MALE]).
								((!$setup["isAvoidYearIfCurrent"] && $isCurrentYear) || !$isCurrentYear ?
									", ".
									date(($setup["isBrief"] && $setup["isShortYear"] ? "y" : "Y"), $timestamp)
								: null);
							break;
					}
				}

				if ($setup["isHours"]) {
					$r .=
						($setup["isBrief"] ? " " : " ".Engine::e()->Translation->getFromArray($this->texts["prepositionAt"], $setup["language"] ?? null)." ");

					if ($setup["hoursFormat"] == \Cherrycake\Modules\Locale\Locale::HOURS_FORMAT_12H)
						$r .= date(" h:i".($setup["isSeconds"] ? ".s" : "")." a", $timestamp);
					else
					if ($setup["hoursFormat"] == \Cherrycake\Modules\Locale\Locale::HOURS_FORMAT_24H)
						$r .= date(" H:i".($setup["isSeconds"] ? ".s" : ""), $timestamp);
				}

				break;

			case \Cherrycake\Modules\Locale\Locale::TIMESTAMP_FORMAT_RELATIVE_HUMAN:
				// If in the past
				if ($timestamp < time()) {

					// Check is yesterday
					if (mktime(0, 0, 0, date("n", $timestamp), date("j", $timestamp), date("Y", $timestamp)) == mktime(0, 0, 0, date("n"), date("j")-1, date("Y"))) {
						$r = Engine::e()->Translation->getFromArray($this->texts["yesterday"], $setup["language"] ?? null);
						break;
					}

					$minutesAgo = floor((time() - $timestamp) / 60);

					if ($minutesAgo < 5) {
						$r = Engine::e()->Translation->getFromArray($this->texts["justNow"], $setup["language"] ?? null);
						break;
					}

					if ($minutesAgo < 60) {
						$r =
							Engine::e()->Translation->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? null).
							$minutesAgo.
							" ".
							($minutesAgo == 1 ? Engine::e()->Translation->getFromArray($this->texts["minute"], $setup["language"] ?? null) : Engine::e()->Translation->getFromArray($this->texts["minutes"], $setup["language"] ?? null)).
							" ".
							Engine::e()->Translation->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? null);
						break;
					}

					$hoursAgo = floor($minutesAgo / 60);

					if ($hoursAgo < 24) {
						$r =
							Engine::e()->Translation->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? null).
							$hoursAgo.
							" ".
							($hoursAgo == 1 ? Engine::e()->Translation->getFromArray($this->texts["hour"], $setup["language"] ?? null) : Engine::e()->Translation->getFromArray($this->texts["hours"], $setup["language"] ?? null)).
							" ".
							Engine::e()->Translation->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? null);
						break;
					}

					$daysAgo = floor($hoursAgo / 24);

					if ($daysAgo < 30) {
						$r =
							Engine::e()->Translation->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? null).
							$daysAgo.
							" ".
							($daysAgo == 1 ? Engine::e()->Translation->getFromArray($this->texts["day"], $setup["language"] ?? null) : Engine::e()->Translation->getFromArray($this->texts["days"], $setup["language"] ?? null)).
							" ".
							Engine::e()->Translation->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? null);
						break;
					}

					$monthsAgo = date("Ym")-date("Ym", $timestamp);

					if ($monthsAgo < 4) {
						$r =
							Engine::e()->Translation->getFromArray($this->texts["agoPrefix"], $setup["language"] ?? null).
							$monthsAgo.
							" ".
							($monthsAgo == 1 ? Engine::e()->Translation->getFromArray($this->texts["month"], $setup["language"] ?? null) : Engine::e()->Translation->getFromArray($this->texts["months"], $setup["language"] ?? null)).
							" ".
							Engine::e()->Translation->getFromArray($this->texts["agoSuffix"], $setup["language"] ?? null);
						break;
					}

				}

				// Other cases: Future timestamps, and timestamps not handled by the humanizer above
				$monthNames = Engine::e()->Translation->getFromArray($this->texts["monthsLong"], $setup["language"] ?? null);
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
	public function formatNumber($number, $setup = false) {
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
			[\Cherrycake\Modules\Locale\Locale::DECIMAL_MARK_POINT => ".", \Cherrycake\Modules\Locale\Locale::DECIMAL_MARK_COMMA => ","][$setup["decimalMark"]],
			$setup["isSeparateThousands"] ? [\Cherrycake\Modules\Locale\Locale::DECIMAL_MARK_POINT => ",", \Cherrycake\Modules\Locale\Locale::DECIMAL_MARK_COMMA => "."][$setup["decimalMark"]] : false
		);
	}

	/**
	 * Formats the given amount as a currency
	 *
	 * @param float $amount
	 * @param array $setup An optional hash array with setup options, with the following possible keys:
	 * * currency: The currency to format the given amount to. One of the available CURRENCY_?. If not specified, the current Locale setting is used.
	 */
	public function formatCurrency($amount, $setup = false) {
		switch ($this->getCurrency()) {
			case \Cherrycake\Modules\Locale\Locale::CURRENCY_USD:
				return "USD".$this->formatNumber($amount, [
					"isSeparateThousands" => true,
					"decimals" => 2
				]);
				break;
			case \Cherrycake\Modules\Locale\Locale::CURRENCY_EURO:
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
	public function getAbbreviatedOrdinal($number, $setup = false) {
		if (!$setup["language"])
			$setup["language"] = $this->getLanguage();

		switch ($setup["language"]) {
			case \Cherrycake\Modules\Locale\Locale::ENGLISH:
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

			case \Cherrycake\Modules\Locale\Locale::SPANISH:
				$r = $number."º";
				break;
		}

		return $r;
	}

	/**
	 * This method tries to detect the user's location using the configured geolocationMethod. If contry-only methods like GEOLOCATION_METHOD_CLOUDFLARE are configured, only the country will be set in the returned Location object.
	 * @return mixed A Location object specifying the user's location, or false if it could not be determined.
	 */
	public function guessLocation() {
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
