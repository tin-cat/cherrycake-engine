<?php

namespace Cherrycake;

// General
const ANSI_NOCOLOR = "\033[0m";
const ANSI_BLACK = "\033[0;30m";
const ANSI_RED = "\033[0;31m";
const ANSI_GREEN = "\033[0;32m";
const ANSI_ORANGE = "\033[0;33m";
const ANSI_BLUE = "\033[0;34m";
const ANSI_PURPLE = "\033[0;35m";
const ANSI_CYAN = "\033[0;36m";
const ANSI_LIGHT_GRAY = "\033[0;37m";
const ANSI_DARK_GRAY = "\033[1;90m";
const ANSI_LIGHT_RED = "\033[35m";
const ANSI_LIGHT_GREEN = "\033[1;32m";
const ANSI_YELLOW = "\033[1;33m";
const ANSI_LIGHT_BLUE = "\033[36m";
const ANSI_LIGHT_PURPLE = "\033[1;35m";
const ANSI_LIGHT_CYAN = "\033[1;36m";
const ANSI_WHITE = "\033[1;37m";

const MODULE_LOADING_ORIGIN_MANUAL = 0;
const MODULE_LOADING_ORIGIN_BASE = 1;
const MODULE_LOADING_ORIGIN_DEPENDENCY = 2;
const MODULE_LOADING_ORIGIN_AUTOLOAD = 3;
const MODULE_LOADING_ORIGIN_GETTER = 4;

// Errors
const ERROR_SYSTEM = 0; // Errors caused by bad programming
const ERROR_APP = 1; // Errors caused by bad usering
const ERROR_NOT_FOUND = 2; // Errors caused when something requested was not found
const ERROR_NO_PERMISSION = 3; // Errors causes when the user didn't have permission to access what they've requested

// Cache
const CACHE_TTL_1_MINUTE = 60;
const CACHE_TTL_5_MINUTES = 300;
const CACHE_TTL_10_MINUTES = 600;
const CACHE_TTL_30_MINUTES = 1800;
const CACHE_TTL_1_HOUR = 3600;
const CACHE_TTL_2_HOURS = 7200;
const CACHE_TTL_6_HOURS = 21600;
const CACHE_TTL_12_HOURS = 43200;
const CACHE_TTL_1_DAY = 86400;
const CACHE_TTL_2_DAYS = 172800;
const CACHE_TTL_3_DAYS = 259200;
const CACHE_TTL_5_DAYS = 432000;
const CACHE_TTL_1_WEEK = 604800;
const CACHE_TTL_2_WEEKS = 1209600;
const CACHE_TTL_1_MONTH = 2592000;

const CACHE_TTL_MINIMAL = 10;
const CACHE_TTL_CRITICAL = CACHE_TTL_1_MINUTE;
const CACHE_TTL_SHORT = CACHE_TTL_5_MINUTES;
const CACHE_TTL_NORMAL = CACHE_TTL_1_HOUR;
const CACHE_TTL_UNCRITICAL = CACHE_TTL_1_DAY;
const CACHE_TTL_LONG = CACHE_TTL_1_WEEK;
const CACHE_TTL_LONGEST = CACHE_TTL_1_MONTH;

// Security
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
const SECURITY_RULE_UPLOADED_FILE = 13; // The value must be a valid uploaded file. A value can be specified that must be an array of keys with setup options for the checkUploadedFile method.
const SECURITY_RULE_UPLOADED_FILE_IMAGE = 14; // The value must be an uploaded image. A value can be specified that must be an array of keys with setup options for the checkUploadedFile method.
const SECURITY_RULE_SQL_INJECTION = 100; // The value must not contain SQL injection suspicious strings
const SECURITY_RULE_TYPICAL_ID = 1000; // Same as SECURITY_RULE_NOT_EMPTY + SECURITY_RULE_INTEGER + SECURITY_RULE_POSITIVE

const SECURITY_FILTER_XSS = 0; // The value is purified to try to remove XSS attacks
const SECURITY_FILTER_STRIP_TAGS = 1; // HTML tags are removed from the value
const SECURITY_FILTER_TRIM = 2; // Spaces at the beggining and at the end of the value are trimmed
const SECURITY_FILTER_JSON = 3; // Decodes json data

// Actions
const ACTION_MODULE_TYPE_CORE = 0;
const ACTION_MODULE_TYPE_APP = 1;

const REQUEST_PARAMETER_TYPE_GET = 0;
const REQUEST_PARAMETER_TYPE_POST = 1;
const REQUEST_PARAMETER_TYPE_FILE = 2;
const REQUEST_PARAMETER_TYPE_CLI = 3;

const REQUEST_PATH_COMPONENT_TYPE_FIXED = 0;
const REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING = 1;
const REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC = 2;

const AJAXRESPONSEJSON_SUCCESS = 0;
const AJAXRESPONSEJSON_ERROR = 1;

const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NONE = 0;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_NOTICE = 1;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP = 2;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_POPUP_MODAL = 3;
const AJAXRESPONSEJSON_UI_MESSAGE_TYPE_CONSOLE = 4;

// Locale
const LANGUAGE_SPANISH = 1;
const LANGUAGE_ENGLISH = 2;
const LANGUAGE_CATALAN = 3;
const LANGUAGE_FRENCH = 4;

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
