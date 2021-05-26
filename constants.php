<?php

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

// Output
const RESPONSE_OK = 200;
const RESPONSE_NOT_FOUND = 404;
const RESPONSE_NO_PERMISSION = 403;
const RESPONSE_INTERNAL_SERVER_ERROR = 500;
const RESPONSE_REDIRECT_MOVED_PERMANENTLY = 301;
const RESPONSE_REDIRECT_FOUND = 302;

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

// Email
const EMAIL_SMTP_ENCRYPTION_TLS = 0;
const EMAIL_SMTP_ENCRYPTION_SSL = 1;

// Css
const CSS_MEDIAQUERY_TABLET = 0; // Matches tablets in all orientations
const CSS_MEDIAQUERY_TABLET_PORTRAIT = 1; // Matches tablets in portrait orientation
const CSS_MEDIAQUERY_TABLET_LANDSCAPE = 2; // Matches tablets in landscape orientation
const CSS_MEDIAQUERY_PHONE = 3; // Matches phones in all orientations
const CSS_MEDIAQUERY_PHONE_PORTRAIT = 4; // Matches phones in portrait orientation
const CSS_MEDIAQUERY_PHONE_LANDSCAPE = 5; // Matches phones in landscape orientation
const CSS_MEDIAQUERY_PORTABLES = 6; // Matches all portable devices and any other small-screen devices (tablets, phones and similar) in all orientations

// Database
const DATABASE_FIELD_TYPE_INTEGER = 0;
const DATABASE_FIELD_TYPE_TINYINT = 1;
const DATABASE_FIELD_TYPE_FLOAT = 2;
const DATABASE_FIELD_TYPE_DATE = 3;
const DATABASE_FIELD_TYPE_DATETIME = 4;
const DATABASE_FIELD_TYPE_TIMESTAMP = 5;
const DATABASE_FIELD_TYPE_TIME = 6;
const DATABASE_FIELD_TYPE_YEAR = 7;
const DATABASE_FIELD_TYPE_STRING = 8;
const DATABASE_FIELD_TYPE_TEXT = 9;
const DATABASE_FIELD_TYPE_BLOB = 10;
const DATABASE_FIELD_TYPE_BOOLEAN = 11;
const DATABASE_FIELD_TYPE_IP = 12;
const DATABASE_FIELD_TYPE_SERIALIZED = 13;
const DATABASE_FIELD_TYPE_COLOR = 14;

const DATABASE_FIELD_DEFAULT_VALUE = 0;
const DATABASE_FIELD_DEFAULT_VALUE_DATE = 1;
const DATABASE_FIELD_DEFAULT_VALUE_DATETIME = 2;
const DATABASE_FIELD_DEFAULT_VALUE_TIMESTAMP = 3;
const DATABASE_FIELD_DEFAULT_VALUE_TIME = 4;
const DATABASE_FIELD_DEFAULT_VALUE_YEAR = 5;
const DATABASE_FIELD_DEFAULT_VALUE_IP = 6;
const DATABASE_FIELD_DEFAULT_VALUE_AVAILABLE_URL_SHORT_CODE = 7;

// Itemadmin
const FORM_ITEM_TYPE_NUMERIC = 0;
const FORM_ITEM_TYPE_STRING = 1;
const FORM_ITEM_TYPE_TEXT = 2;
const FORM_ITEM_TYPE_BOOLEAN = 3;
const FORM_ITEM_TYPE_RADIOS = 4;
const FORM_ITEM_TYPE_SELECT = 5;
const FORM_ITEM_TYPE_DATABASE_QUERY = 6;
const FORM_ITEM_TYPE_COUNTRY = 7;

const FORM_ITEM_META_TYPE_MULTILEVEL_SELECT = 0;
const FORM_ITEM_META_TYPE_LOCATION = 1;

// Janitor
const JANITORTASK_EXECUTION_RETURN_WARNING = 0; // Return code for JanitorTask run when task returned a warning.
const JANITORTASK_EXECUTION_RETURN_ERROR = 1; // Return code for JanitorTask run when task returned an error.
const JANITORTASK_EXECUTION_RETURN_CRITICAL = 2; // Return code for JanitorTask run when task returned a critical error.
const JANITORTASK_EXECUTION_RETURN_OK = 3; // Return code for JanitorTask run when task was executed without errors.

const JANITORTASK_EXECUTION_PERIODICITY_ONLY_MANUAL = 0; // The task can only be executed when calling the Janitor run process with an specific task parameter.
const JANITORTASK_EXECUTION_PERIODICITY_ALWAYS = 1; // The task will be executed every time Janitor run is called.
const JANITORTASK_EXECUTION_PERIODICITY_EACH_SECONDS = 2; // The task will be executed every specified seconds. Seconds are specified in "periodicityEachSeconds" config key.
const JANITORTASK_EXECUTION_PERIODICITY_MINUTES = 3; // The task will be executed on the given minutes of each hour. Desired minutes are specified as an array in the "periodicityMinutes" config key. For example: [0, 15, 30, 45]
const JANITORTASK_EXECUTION_PERIODICITY_HOURS = 4; // The task will be executed on the given hours of each day. Desired hours/minute are specified as an array in the "periodicityHours" config key in the syntax ["hour:minute", ...] For example: ["00:00", "10:45", "20:15"]
const JANITORTASK_EXECUTION_PERIODICITY_DAYSOFMONTH = 5; // The task will be executed on the given days of each month. Desired days/hour/minute are specified as an array in the "periodicityDaysOfMonth" config key in the syntax ["day@hour:minute", ...] For example: ["1@12:00", "15@18:30", "20@00:00"] (Take into account days of month that do not exist)

// Login
const LOGIN_PASSWORD_ENCRYPTION_METHOD_PBKDF2 = 0;

const LOGIN_RESULT_OK = 0;
const LOGIN_RESULT_FAILED = 1;
const LOGIN_RESULT_FAILED_UNKNOWN_USER = 2;
const LOGIN_RESULT_FAILED_WRONG_PASSWORD = 3;

const LOGOUT_RESULT_OK = 0;
const LOGOUT_RESULT_FAILED = 1;

// Stats
const STATS_EVENT_TIME_RESOLUTION_MINUTE = 0;
const STATS_EVENT_TIME_RESOLUTION_HOUR = 1;
const STATS_EVENT_TIME_RESOLUTION_DAY = 2;
const STATS_EVENT_TIME_RESOLUTION_MONTH = 3;
const STATS_EVENT_TIME_RESOLUTION_YEAR = 4;

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
