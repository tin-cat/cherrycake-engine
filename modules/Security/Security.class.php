<?php

/**
 * Security
 *
 * @package Cherrycake
 */

namespace Cherrycake;

const SECURITY_RULE_NOT_NULL = 0; // The value must be not null (typically used to check whether a parameter has been passed or not. An empty field in a form will not trigger this rule)
const SECURITY_RULE_NOT_EMPTY = 1; // The value must not be empty (typically used to check whether a parameter has been passed or not. An empty field in a form _will_ trigger this rule)
const SECURITY_RULE_INTEGER = 2; // The value must be an integer (-infinite to +infinite without decimals)
const SECURITY_RULE_POSITIVE = 3; // The value must be positive (0 to +infinite)
const SECURITY_RULE_MAX_VALUE = 4; // The value must be a number less than or equal the specified value
const SECURITY_RULE_MIN_VALUE = 5; // The value must be a number greater than or equal the specified value
const SECURITY_RULE_MAX_CHARS = 6; // The value must be less than or equal the specified number of chars
const SECURITY_RULE_MIN_CHARS = 7; // The value must be bigger than or equal the specified number of chars
const SECURITY_RULE_BOOLEAN = 8; // The value must be either a 0 or a 1
const SECURITY_RULE_SLUG = 9; // The value must have the typical URL slug code syntax, containing only numbers and letters from A to Z both lower and uppercase, and -_ characters
const SECURITY_RULE_URL_SHORT_CODE = 10; // The value must have the typical URL short code syntax, containing only numbers and letters from A to Z both lower and uppercase
const SECURITY_RULE_URL_ROUTE = 11; // The value must have the typical URL slug code syntax, like SECURITY_RULE_SLUG plus the "/" character
const SECURITY_RULE_LIMITED_VALUES = 12; // The value must be exactly one of the specified values.
const SECURITY_RULE_SQL_INJECTION = 100; // The value must not contain SQL injection suspicious strings
const SECURITY_RULE_TYPICAL_ID = 1000; // Same as SECURITY_RULE_NOT_EMPTY + SECURITY_RULE_INTEGER + SECURITY_RULE_POSITIVE

const SECURITY_FILTER_XSS = 0; // The value is purified to try to remove XSS attacks
const SECURITY_FILTER_STRIP_TAGS = 1; // HTML tags are removed from the value
const SECURITY_FILTER_TRIM = 2; // Spaces at the beggining and at the end of the value are trimmed
const SECURITY_FILTER_NORMALIZE_AT_USERNAME = 3; // For usernames with the preceding @, it normalizes them removing the preceding @ if found. Also trims and strips tags.

namespace Cherrycake\Modules;

/**
 * Security
 *
 * Provides security measures.
 * Csrf features require the Session module.
 *
 * Configuration example for security.config.php:
 * <code>
 * $securityConfig = [
 * 	"isCheckMaliciousBadBrowsers" => true, // Whether to check or not for known malicious browserstrings like Havij, defaults to true
 * 	"permanentlyBannedIps" => [ // An array of banned IPs that must be blocked from accessing the application
 * 		"1.1.1.1"
 * 	],
 *	"isAutoBannedIps" => true, // Whether to automatically ban IPs when a hack is detected
 * 	"autoBannedIpsCacheProviderName" => "fast", // The name of the CacheProvider used to store banned Ips
 * 	"autoBannedIpsCacheTtl" => \Cherrycake\Modules\CACHE_TTL_12_HOURS, // The TTL of banned Ips. Auto banned IPs TTL expiration is resetted if more hack detections are detected for that Ip
 *	"autoBannedIpsThreshold" => 10 // The number hack intrusions detected from the same Ip to consider it banned
 * ];
 * </code>
 *
 * @package Cherrycake
 * @category Modules
 */
class Security extends \Cherrycake\Module
{
	/**
	 * @var array $config Default configuration options
	 */
	var $config = [
		"isCheckMaliciousBadBrowsers" => true,
		"isAutoBannedIps" => true,
		"autoBannedIpsCacheTtl" => \Cherrycake\Modules\CACHE_TTL_12_HOURS,
		"autoBannedIpsThreshold" => 10
	];

	/**
	 * @var array $dependentCherrycakeModules Cherrycake module names that are required by this module
	 */
	var $dependentCherrycakeModules = [
		"Output",
		"Errors",
		"Cache"
	];

	/**
	 * @var array $fixedParameterRules Contains the rules that must be always met when checking parameter values
	 */
	var $fixedParameterRules = [
		\Cherrycake\SECURITY_RULE_SQL_INJECTION
	];

	/**
	 * @var array $fixedParametersFilters Contains the filters that must be always applied when retrieving parameter values
	 */
	var $fixedParametersFilters = [
		\Cherrycake\SECURITY_FILTER_XSS
	];

	var $sqlInjectionDetectRegexp = "[insert( *)into|delete( *)from|alter( *)table|drop( *)table|drop( *)database|select( *)select|union( *)all|select( *)union|select( *)count|waitfor( *)delay|information_schema|limit( +)0|select( +)1|,null|rand\(|\tables|1=1|0x31303235343830303536]i";

	var $maliciousBrowserStringsRegexp = "[Havij|WinInet Test]i";

	/**
	 * init
	 *
	 * Initializes the module.
	 * Performs the init security checks.
	 *
	 * @return boolean Whether the module has been initted ok
	 */
	function init() {
		$this->isConfigFile = true;
		if (!parent::init())
			return false;

		// Check permanently banned Ips
		if ($this->getConfig("permanentlyBannedIps"))
			if (in_array($this->getClientIp(), $this->getConfig("permanentlyBannedIps"))) {
				global $e;
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Security",
					"description" => "Permanently banned Ip trying to access",
					"data" => [
						"ip" => $this->getClientIp()
					]
				]));
				return false;
			}

		// Check automatically banned Ips
		if ($this->getConfig("isAutoBannedIps"))
			if ($this->isAutoBannedIp()) {
				global $e;
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Security",
					"description" => "Automatically banned Ip access trying to access",
					"data" => [
						"ip" => $this->getClientIp()
					]
				]));
				return false;
			}

		// Check malicious browserstrings
		if ($this->getConfig("isCheckMaliciousBadBrowsers"))
			if (preg_match($this->maliciousBrowserStringsRegexp, $this->getClientBrowserString())) {
				global $e;
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Security",
					"description" => "Malicious browserstring detected",
					"data" => [
						"ip" => $this->getClientIp()
					]
				]));
				return false;
			}

		return true;
	}

	/**
	 * Checks the given value against the fixed rules and the additional specific given ones
	 *
	 * @param mixed $value The value to check
	 * @param array $rules A hash array of the check rules to perform, with the syntax:
	 * {
	 *	<RULE>,
	 *	{
	 * 		<RULE>,
	 * 		<additional parameter to the rule>
	 *	},
	 *	...
	 * }
	 * @param boolean $isFixedRules Whether to also check for the fixed rules or not
	 *
	 * @return Result A Result object with optionally the following additional payloads:
	 * * description: An array containing the list of errors found when checking the value
	 */
	function checkValue($value = NULL, $rules = false, $isFixedRules = true) {
		if (!is_array($rules))
			$rules = [];

		if ($isFixedRules)
			$rules = array_merge($this->fixedParameterRules, $rules);

		if (!$rules)
			return new \Cherrycake\ResultOk;

		$isError = false;

		foreach ($rules as $rule) {

			$ruleParameter = false;

			if (is_array($rule)) { 
				$ruleParameter = $rule[1];
				$rule = $rule[0];
			}

			if ($rule == \Cherrycake\SECURITY_RULE_SQL_INJECTION)
				if (preg_match($this->sqlInjectionDetectRegexp, $value)) {
					$isError = true;
					$description[] = "is suspicious of SQL injection";
					$this->autoBanIp();
					break;
				}

			if ($rule == \Cherrycake\SECURITY_RULE_NOT_NULL)
				if (is_null($value)) {
					$isError = true;
					$description[] = "Parameter not passed";
					break;
				}

			if ($rule == \Cherrycake\SECURITY_RULE_NOT_EMPTY || $rule == \Cherrycake\SECURITY_RULE_TYPICAL_ID)
				if (trim($value) == "") {
					$isError = true;
					$description[] = "Parameter is empty";
					break;
				}

			if ($rule == \Cherrycake\SECURITY_RULE_INTEGER || $rule == \Cherrycake\SECURITY_RULE_TYPICAL_ID)
				if ($value && (!is_numeric($value) || stristr($value, "."))) {
					$isError = true;
					$description[] = "Parameter is not integer";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_POSITIVE || $rule == \Cherrycake\SECURITY_RULE_TYPICAL_ID)
				if ($value < 0) {
					$isError = true;
					$description[] = "Parameter is not positive";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_MAX_VALUE)
				if ($value > $ruleParameter) {
					$isError = true;
					$description[] = "Parameter is greater than ".$ruleParameter;
				}

			if ($rule == \Cherrycake\SECURITY_RULE_MIN_VALUE)
				if ($value < $ruleParameter) {
					$isError = true;
					$description[] = "Parameter is less than ".$ruleParameter;
				}

			if ($rule == \Cherrycake\SECURITY_RULE_MAX_CHARS)
				if (strlen($value) > $ruleParameter) {
					$isError = true;
					$description[] = "Parameter is bigger than ".$ruleParameter." characters";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_MIN_CHARS)
				if (strlen($value) < $ruleParameter) {
					$isError = true;
					$description[] = "Parameter is less than ".$ruleParameter." characters";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_BOOLEAN)
				if (intval($value) !== 0 && intval($value) !== 1) {
					$isError = true;
					$description[] = "Parameter is not boolean";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_SLUG)
				if (preg_match("/[^0-9A-Za-z\-_]/", $value)) {
					$isError = true;
					$description[] = "Parameter is not a slug";
				}
			
			if ($rule == \Cherrycake\SECURITY_RULE_URL_SHORT_CODE)
				if (preg_match("/[^0-9A-Za-z]/", $value)) {
					$isError = true;
					$description[] = "Parameter is not a url short code";
				}

			if ($rule == \Cherrycake\SECURITY_RULE_URL_ROUTE)
				if (preg_match("/[^0-9A-Za-z\-_\/]/", $value)) {
					$isError = true;
					$description[] = "Parameter is not an URL route";
				}
			
			if ($rule == \Cherrycake\SECURITY_RULE_LIMITED_VALUES) {
				$isError = true;
				foreach ($ruleParameter as $possibleValue)
					if (strcmp($possibleValue, $value) == 0)
						$isError = false;
				if ($isError)
					$description[] = "Parameter hasn't any of the possible values [".implode("|",$ruleParameter)."]";
			}
		}

		if ($isError)
			return new \Cherrycake\ResultKo([
				"description" => $description
			]);
		else
			return new \Cherrycake\ResultOk;
	}

	/**
	 * Filters the given value with the fixed rules and the additional specific given ones
	 *
	 * @param mixed $value The value to filter
	 * @param array $filters A hash array of the filters to apply, with the syntax:
	 * {
	 *	<FILTER>,
	 *	{
	 * 		<FILTER>,
	 * 		<additional parameter to the filter>
	 *	},
	 *	...
	 * }
	 * @param boolean $isFixedFilters Whether to also apply the fixed filters or not
	 *
	 * @return mixed The filtered value
	 */
	function filterValue($value = NULL, $filters = false, $isFixedFilters = true) {
		if (!is_array($filters))
			$filters = [];

		if ($isFixedFilters)
			$filters = array_merge($this->fixedParametersFilters, $filters);

		if (!$filters)
			return new \Cherrycake\ResultOk;

		$isError = false;

		foreach ($filters as $filter) {

			$filterParameter = false;

			if (is_array($filter)) { 
				$filterParameter = $filter[1];
				$filter = $filter[0];
			}

			if ($filter == \Cherrycake\SECURITY_FILTER_XSS) {
				$value = $this->stripXss($value);
			}
			
			if ($filter == \Cherrycake\SECURITY_FILTER_STRIP_TAGS || $filter == \Cherrycake\SECURITY_FILTER_NORMALIZE_AT_USERNAME) {
				$value = strip_tags($value);
			}
			
			if ($filter == \Cherrycake\SECURITY_FILTER_TRIM || $filter == \Cherrycake\SECURITY_FILTER_NORMALIZE_AT_USERNAME) {
				$value = trim($value);
			}

			if ($filter == \Cherrycake\SECURITY_FILTER_NORMALIZE_AT_USERNAME) {
				if (substr($value, 0, 1) == "@")
						$value = substr($value, 1);
			}
		}

		return $value;
	}

	/**
	 * Checks the given Request object for security problems
	 * @param Request $request The Request object to check
	 * @return boolean True if no issues found during checking, false otherwise.
	 */
	function checkRequest($request) {
		global $e;

		if ($request->isSecurityCsrf()) {
			// Check host
			if ($_SERVER["HTTP_ORIGIN"])
				$origin = $_SERVER["HTTP_ORIGIN"];
			else
			if ($_SERVER["HTTP_REFERER"])
				$origin = $_SERVER["HTTP_REFERER"];
			if ($origin) {
				if ($parsedOrigin = parse_url($origin)) {
					if (strcmp($parsedOrigin["host"], $_SERVER["SERVER_NAME"]) !== 0) {
						$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
							"subType" => "Csrf",
							"description" => "CSRF Attack detected: Header reported origin host does not matches the server reported host",
							"data" => [
									"HTTP_ORIGIN" => $_SERVER["HTTP_ORIGIN"],
									"HTTP_REFERER" => $_SERVER["HTTP_REFERER"],
									"parsedOrigin Host" => $parsedOrigin["host"],
									"SERVER_NAME" => $_SERVER["SERVER_NAME"]
								]
						]));
						return false;
					}
				}
			}

			// Check csrf token
			if (!$request->isParameterReceived("csrfToken")) {
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Csrf",
					"description" => "CSRF Attack detected: No token parameter received"
				]));
				return false;
			}

			if (!$this->isCsrfTokenInSession()) {
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Csrf",
					"description" => "CSRF Attack detected: No token in session"
				]));
				return false;
			}

			if (!hash_equals($this->getCsrfTokenInSession(), $request->csrfToken)) {
				$e->SystemLog->event(new \Cherrycake\SystemLogEventHack([
					"subType" => "Csrf",
					"description" => "CSRF Attack detected: Token parameter does not matches token in session"
				]));
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the token for this session. If no token stored yet, creates one and stores it in the session.
	 * @return string The token
	 */
	function getCsrfToken() {
		if ($this->isCsrfTokenInSession())
			return $this->getCsrfTokenInSession();
		$token = $this->generateCsrfToken();
		$this->setCsrfTokenInSession($token);
		return $token;
	}

	/**
	 * @return boolean Whether there is a Csrf token stored on this session
	 */
	function isCsrfTokenInSession() {
		global $e;
		$e->loadCherrycakeModule("Session");
		return isset($e->Session->csrfToken);
	}

	/**
	 * @return string The token stored in session. False if none was present in session.
	 */
	function getCsrfTokenInSession() {
		global $e;
		$e->loadCherrycakeModule("Session");
		return $e->Session->csrfToken;
	}

	/**
	 * Stores the given token in the current session
	 * @param boolean True if everything went ok, false otherwise.
	 */
	function setCsrfTokenInSession($token) {
		global $e;
		$e->loadCherrycakeModule("Session");
		$e->Session->csrfToken = $token;
		return true;
	}

	/**
	 * @return string A secure token suitable for being stored in the session and being used to detect Csrf attacks.
	 */
	function generateCsrfToken() {
		return bin2hex(random_bytes(32));
	}

	/**
	 * Generates a valid slug based on the given string
	 * @param string $string The base string
	 * @param string $separator The separator to use when needed
	 * @param integer $minLength The minimum length of the generated slug. Resulting slugs shorter than this will be considered invalid and return false.
	 * @param integer $maxLength The maximum length of the generated slug. Resulting slugs will be cut to this maximum length
	 * @param boolean $isLowercase Whether to generate an all-lowercase slug or not
	 * @param boolean $isAllowNumeric Whether to allow for slugs that are entirely numeric or not
	 * @return mix The slug, or false if it couldn't be generated
	 */
	function generateSlug($string, $separator = "-", $minLength = 1, $maxLength = 140, $isLowercase = true, $isAllowNumeric = true) {
		$toreplace = [
			"/€/" => "E",
			"/á|à|ä|Á|À|Ä|Â|â/" => "a",
			"/é|è|ë|É|È|Ë|Ê|ê/" => "e",
			"/í|í|ï|Í|Ì|Ï|Î|î/" => "i",
			"/ó|ò|ö|Ó|Ò|Ö|Ô|ô/" => "o",
			"/ú|ù|ü|Ú|Ù|Ü|Û|û/" => "u",
			"/ñ|Ñ/" => "n",
			"/\s|_|,|:|'|\"|\+|\/|·/" => $separator,
			"/--+/" => $separator,
			"/\%|\?|¿|\!|¡|&|\(|\)/" => ""
		];

		$allowedCharacters = "abcdefghijklmnopqrstuvwxyz0123456789";
		$extraAllowedCharacters = ".".$separator;

		while(list($search, $replace) = each($toreplace))
			$string = preg_replace($search, $replace, $string);

		if ($isLowercase)
			$string = mb_strtolower($string);

		// Filter the string to end only with the $allowedCharacters.$extraAllowedCharacters
		$finalString = "";
		for ($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			if (stristr($allowedCharacters.$extraAllowedCharacters, $char))
				$finalString .= $char;
		}

		// Remove repeated separators
		$finalString = preg_replace("/(".$separator.")\\1+/", "$1", $finalString);

		// Remove trailing and leading $extraAllowedCharacters
		if (stristr($extraAllowedCharacters, substr($finalString, strlen($finalString)-1, 1)))
			$finalString = substr($finalString, 0, strlen($finalString)-1);

		if (stristr($extraAllowedCharacters, substr($finalString, 0, 1)))
			$finalString = substr($finalString, 1);

		if (strlen($finalString) < 1)
			return false;

		if ($maxLength > 0 && strlen($finalString) > $maxLength)
			$finalString = substr($finalString, 0, $maxLength);

		if ($minLength !== false && strlen($finalString) < $minLength)
			return false;

		if (!$isAllowNumeric && is_numeric($finalString))
			return false;

		return $finalString;
	}

	/**
	 * isAutoBannedIp
	 *
	 * Checks if the given Ip is auto banned
	 *
	 * @param $ip The Ip to check. The current client's Ip is used if not specified
	 * @return boolean Whether the given Ip has been auto banned or not
	 */
	function isAutoBannedIp($ip = false) {
		global $e;

		if (!$ip)
			$ip = $this->getClientIp();

		$cacheKey = "autoBannedIp_".$ip;
		$cacheProviderName = $this->getConfig("autoBannedIpsCacheProviderName");

		if ($e->Cache->$cacheProviderName->get($cacheKey) > $this->getConfig("autoBannedIpsThreshold"))
			return true;
		else
			return false;
	}

	/**
	 * autoBanIp
	 *
	 * Adds the given Ip to the automatically banned Ips list. If the Ip is already on the list, the TTL is updated
	 * @param $ip The Ip to ban. The current client's Ip is used if not specified
	 */
	function autoBanIp($ip = false) {
		global $e;

		if (!$ip)
			$ip = $this->getClientIp();

		$cacheKey = "autoBannedIp_".$ip;
		$cacheProviderName = $this->getConfig("autoBannedIpsCacheProviderName");

		if ($e->Cache->$cacheProviderName->get($cacheKey)) {
			$e->Cache->$cacheProviderName->increment($cacheKey);
			$e->Cache->$cacheProviderName->touch($cacheKey, $this->getConfig("autoBannedIpsCacheTtl"));
		}
		else
			$e->Cache->$cacheProviderName->set($cacheKey, 1, $this->getConfig("autoBannedIpsCacheTtl"));
	}

	/**
	 * removeAutoBannedIp
	 *
	 * Deletes the given Ip to the automatically banned Ips list. If the Ip is already on the list, the TTL is updated
	 * @param $ip The Ip to unban. The current client's Ip is used if not specified
	 */
	function removeAutoBannedIp($ip = false) {
		global $e;

		if (!$ip)
			$ip = $this->getClientIp();

		$cacheKey = "autoBannedIp_".$ip;
		$cacheProviderName = $this->getConfig("autoBannedIpsCacheProviderName");

		$e->Cache->$cacheProviderName->delete($cacheKey);
	}

	/**
	 * getClientIp
	 *
	 * @return string The client's IP
	 */
	function getClientIp() {
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * getClientBrowserString
	 *
	 * @return string The client's browserstring
	 */
	function getClientBrowserString() {
		return $_SERVER["HTTP_USER_AGENT"];
	}

	/**
	 * Cleans string coming from untrusted sources like user input. Should prevent XSS attacks.
	 * @param  string $string The string to clean
	 * @return string The cleaned string
	 */
	function clean($string) {
		require_once LIB_DIR."/vendor/autoload.php";
		$config = \HTMLPurifier_Config::createDefault();

		$config->set('Core.Encoding', 'UTF-8');
		$config->set('HTML.Doctype', 'XHTML 1.0 Transitional');

		$purifier = new \HTMLPurifier($config);
		return $purifier->purify($string);
	}

	/**
	 * Removes XSS attacks from the given string
	 * @param string $string The string
	 * @return string The string with XSS attacks removed
	 */
	function stripXss($string) {
		return $this->clean($string);
	}

}