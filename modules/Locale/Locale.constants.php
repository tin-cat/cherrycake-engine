<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

const LANGUAGE_SPANISH = 1;
const LANGUAGE_ENGLISH = 2;

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

const TIMESTAMP_FORMAT_BASIC = 0;
const TIMESTAMP_FORMAT_HUMAN = 1;
const TIMESTAMP_FORMAT_RELATIVE_HUMAN = 2;

const ORDINAL_GENDER_MALE = 0;
const ORDINAL_GENDER_FEMALE = 1;

const GEOLOCATION_METHOD_CLOUDFLARE = 0;