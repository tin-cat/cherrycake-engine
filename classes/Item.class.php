<?php

/**
 * Item
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Item
 *
 * Class that represents a generic item from a database.
 *
 * @package Cherrycake
 * @category Classes
 */
class Item extends BasicObject {
	/**
	 * @var string The name of the database provider to use when querying the database for this item.
	 */
	protected $databaseProviderName = "main";

	/**
	 * @var string The name of the database table where this items are stored.
	 */
	protected $tableName;

	/**
	 * @var string The name of the field on the table that uniquely identifies this item on the database table with a numeric id. It should be an autoincrement field.
	 */
	protected $idFieldName = "id";

	/**
	 * @var string The name of the cache provider to use.
	 */
	protected $cacheProviderName = "fast";

	/**
	 * @var integer The TTL for the cache storage.
	 */
	protected $cacheTtl = \Cherrycake\Modules\CACHE_TTL_NORMAL;

	/**
	 * @var string The string to use as the key for this Item in the cache, the value of the idFieldName will be appended.
	 */
	protected $cacheSpecificPrefix;

	/**
	 * @var string <queryDatabaseCache|queryDatabase> The method to use when loading this item from the database via an index
	 */
	protected $loadFromIdMethod = "queryDatabase";

	/**
	 * @var array Hash array specification of the fields on the database table for this item type, where each key is the field name and the value is a hash array with the following keys:
	 * * type: The type of the field, one of the available \Cherrycake\Modules\DATABASE_FIELD_TYPE_*
	 * * formItem: A hash array containing the specification of this field for forms, used by ItemAdmin
	 * * * type: The type of the form item, one of the available \Cherrycake\Modules\FORM_ITEM_TYPE_*
	 * * * selectType: For FORM_ITEM_TYPE_SELECT type: The select type: either FORM_ITEM_SELECT_TYPE_RADIOS or FORM_ITEM_SELECT_TYPE_COMBO
	 * * * items: For FORM_ITEM_TYPE_SELECT type: A hash array of the items for the selection, where each key is the value
	 * * * * title
	 * * * * subTitle
	 * * isMultiLanguage: Whether this field stores multilanguage data, meaning there are more than one actual fields on the database, one for each available language (as configured in Locale.config.php key availableLanguages)
	 * * title: The title of the field, to be used when representing data on modules like UiComponentTableAdmin
	 * * prefix: The prefix string to add when humanizing the field value
	 * * postfix: The postfix string to add when humanizing the field value
	 * * multiplier: A multiplier to apply when humanizing the field value
	 * * decimals: The number of decimals to show when humanizing the field value
	 * * humanizeMethodName: A method name to call to humanize the field value. It will receive the Item object as the first and only parameter. If this returns something other than null, the returned value will be used and any other humanizing method and configs like prefix, postfix, multiplier, decimals, etc will be omitted.
	 * * humanizePreMethodName: A method name to call with the field value before any other humanization is done. It will receive the Item object as the first and only parameter
	 * * humanizePostMethodName: A method name to call with the field value after any other humanization is done. It will receive the already treated value as the first parameter and the Item object as the second
	 * * representFunction: An anonymous function that will be passed the Item object, the returned value will be shown to represent this field current value in UiComponents such as UiComponentItemAdmin when used in conjunction with ItemAdmin
	 * * requestSecurityRules: An array of security rules from the available \Cherrycake\SECURITY_RULE_* that should be applied whenever receiving values for this field in a request, just like the RequestParameter class accepts. Used for example in ItemAdmin
     * * requestFilters: An array of filter from the available SECURITY_FILTER_* that should be appled whenever receiving values for this field in a request, just like the RequestParameter class accepts. Used for example in ItemAdmin
	 * * validationMethod: An anonymous function to validate the received value for this field, or an array where the first element is the class name, and the second the method name, just like the call_user_func PHP function would expect it. Must return an AjaxResponse object. Used for example in ItemAdmin
	 */
	protected $fields = false;

	/**
	 * @var array Hash array specification of the fields for this item type that are not fields on the database, but instead fields that interact with the database in a special way. For example, a "location" meta field might interact with the database by setting the countryId, regionId and cityId non-meta fields. Each key is the field name.
	 * * formItem: A hash array containing the specification of this field for forms, used by ItemAdmin
	 * * type: The type of the form item, one of the available \Cherrycake\Modules\FORM_ITEM_META_TYPE_*
	 * * * countryIdFieldName: For FORM_ITEM_META_TYPE_MULTILEVEL_SELECT type, the name of the field that holds the country id
	 * * * regionIdFieldName: For FORM_ITEM_META_TYPE_MULTILEVEL_SELECT type, the name of the field that holds the region id
	 * * * cityIdFieldName: For FORM_ITEM_META_TYPE_MULTILEVEL_SELECT type, the name of the field that holds the city id
	 */
	protected $metaFields = false;

	/**
	 * @var string $urlShortCodeCharacters The characters that will be used to generate url short codes
	 */
	protected $urlShortCodeCharacters = "123456789abcdefghijkmnpqrstuvwyzABCDEFGHJKLMNPQRSTUVWXYZ";

	/**
	 * @var integer $minUrlShortCodeCharacters The minimum number of characters that will be used when generating url short codes
	 */
	protected $minUrlShortCodeCharacters = 5;

	/**
	 * @var integer $maxUrlShortCodeTriesForCodeLength When generating url short codes, the maximum number of random url short codes of a given length will be tried before increasing the length by one and keep trying
	 */
	protected $maxUrlShortCodeTriesForCodeLength = 5;

	/**
	 * @var array An array containing the Item data
	 */
	protected $itemData;

	/**
	 * __construct
	 *
	 * Constructor, allows to create an instance object which automatically fills itself using one of the available load methods
	 *
	 * Setup keys:
	 *
	 * * loadMethod: If specified, it loads the Item using the given method, available methods:
	 * 	- fromDatabaseRow: Loads the Item with the given DatabaseRow object data in the setup key "databaseRow"
	 *  - fromId: Loads the item by calling the loadFromId method passing the value of the "id" setup key as the parameter
	 *  - fromData: Loads the item by calling the loadFromData method passing the value of the "data" setup key as the parameter
	 *
	 * Throws an exception if the object could not be constructed
	 * 
	 * @param array $setup Specifications on how to create the Item object
	 */
	function __construct($setup = false) {
		if (isset($setup["loadMethod"]))
			switch($setup["loadMethod"]) {
				case "fromDatabaseRow":
					if (!$this->loadFromDatabaseRow($setup["databaseRow"]))
						throw new \Exception("Couldn't load ".get_called_class()." Item from row");
					break;

				case "fromId":
					if (!$this->loadFromId($setup["id"], $setup["idFieldName"], $setup["loadFromIdMethod"]))
						throw new \Exception("Couldn't load ".get_called_class()." Item from id ".$setup["id"].($setup["idFieldName"] ? " with idFieldName ".$setup["idFieldName"] : null));
					break;

				case "fromData":
					if (!$this->loadFromData($setup["data"]))
						throw new \Exception("Couldn't load ".get_called_class()." Item from data");
					break;
			}
		else
			if (!$this->loadInline($setup))
				throw new \Exception("Couldn't inline load ".get_called_class()." Item");
	}

	/**
	 * @return array The fields for this Item
	 */
	function getFields() {
		return $this->fields;
	}

	/**
	 * @return array The meta fields for this Item
	 */
	function getMetaFields() {
		return $this->metaFields;
	}

	/**
	 * loadFromDatabaseRow
	 *
	 * Fills the Item's data with the given DatabaseRow object data
	 *
	 * @param DatabaseRow $databaseRow
	 * @return boolean True on success, false on error
	 */
	function loadFromDatabaseRow($databaseRow) {
		return $this->loadFromData($databaseRow->getData($this->fields));
	}

	/**
	 * loadFromData
	 *
	 * Fills the Item's data with the given data array
	 *
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function loadFromData($data) {
		$this->itemData = $data;
		return $this->init();
	}

	/**
	 * loadInline
	 *
	 * Loads the item when no loadMethod has been provided on construction. Should be overloaded.
	 *
	 * @param array $data A hash array with the data
	 * @return boolean True on success, false on error
	 */
	function loadInline($data = false) {
		return $this->init();
	}

	/**
	 * Retrieves the item data on the database corresponding to the specified $value for the given $fieldName and fills this Item's with it.
	 * 
	 * @param mixed $id The value to match the $fieldName to.
	 * @param string $fieldName The name of the id field, as defined on this Item's $fields. Should be a field that uniquely identifies a row on the database.
	 * @param mixed $loadMethod The loading method to use. If not specified, it uses the default $loadFromIdMethod. One of the following values:
	 * * queryDatabaseCache
	 * * queryDatabase
	 * @return boolean True if the row was found and the Item was loaded ok, false otherwise.
	 */
	function loadFromId($id, $fieldName = false, $method = false) {
		switch($method ? $method : $this->loadFromIdMethod) {
			case "queryDatabaseCache":
			case "queryDatabase":
				if (!$databaseRow = $this->loadFromIdGetDatabaseRow($fieldName ? $fieldName : $this->idFieldName, $id, $method))
					return false;
				return $this->loadFromDatabaseRow($databaseRow);
				break;
		}

		return $this->init();
	}

	/**
	 * Returns a DatabaseRow object containing the query result of the Item identified by the given id
	 * 
	 * @param string $fieldName The name of the id field, as defined on this Item's $fields. Should be a field that uniquely identifies a row on the database.
	 * @param mixed $id The value to match the $fieldName to.
	 * @param mixed $method The loading method to use. If not specified, it uses the default $loadFromIdMethod. One of the following values:
	 * * queryDatabaseCache
	 * * queryDatabase
	 * @return DatabaseRow A DatabaseRow object containing the result of querying the item with the given id, or false if error
	 */
	function loadFromIdGetDatabaseRow($fieldName, $id, $method = false) {
		switch($method ? $method : $this->loadFromIdMethod) {
			case "queryDatabaseCache":
				global $e;
				if (!$result = $e->Database->{$this->databaseProviderName}->prepareAndExecuteCache(
					$this->getLoadFromIdDatabaseQuery($fieldName),
					[
						[
							"type" => $this->fields[$fieldName]["type"],
							"value" => $id
						]
					],
					$this->cacheTtl,
					array(
						"prefix" => $this->cacheSpecificPrefix,
						"uniqueId" => $fieldName."=".$id
					),
					$this->cacheProviderName,
					false
				))
					return false;
				return $result->getRow();
				break;

			case "queryDatabase":
				global $e;
				if (!$result = $e->Database->{$this->databaseProviderName}->prepareAndExecute(
					$this->getLoadFromIdDatabaseQuery($fieldName),
					[
						[
							"type" => $this->fields[$fieldName]["type"],
							"value" => $id
						]
					]
				))
					return false;
				return $result->getRow();
				break;
		}
	}

	/**
	 * @param string $fieldName The name of the field to match te index of a unique Item on the database to.
	 * @return string The SQL query to request the item of the given index from the Database
	 */
	function getLoadFromIdDatabaseQuery($fieldName) {
		return "select * from ".$this->tableName." where ".$fieldName." = ?";
	}

	/**
	 * clearCache
	 *
	 * Removes this Item from the cache. Can be overloaded if more additional things have to be cleared from cache in relation to the Item.
	 *
	 * @param array $fieldNames An array of field names that have been used to query items by index, so those queries will be cleared from cache. idFieldName and other fields commonily used by this object are automatically added to this array and cleared from cache.
	 * @return boolean True on success, false on failure
	 */
	function clearCache($fieldNames = false) {
		global $e;

		$fieldNames[] = $this->idFieldName;

		$isErrors = false;
		foreach ($fieldNames as $fieldName) {
			if (!$e->Cache->{$this->cacheProviderName}->delete($e->Cache->buildCacheKey([
				"prefix" => $this->cacheSpecificPrefix,
				"uniqueId" => $fieldName."=".$this->{$this->fieldName}
			])))
			$isErrors = true;
		}

		return $isErrors;
	}

	/**
	 * Inserts a row on the database representing an item. This item becomes the created one.
	 * @param array $data Optional fields data that will override the data stored on the object if specified. Fields must be defined on this->fields
	 * For multilanguage fields, a hash array with the syntax [<language code> => <value>, ...] can be passed. If a non-array value is passed the currently detected language will be used
	 * @return boolean True if insertion went ok, false otherwise
	 */
	function insert($data = false) {
		global $e;

		foreach ($this->fields as $fieldName => $fieldData) {
			if ($fieldName == $this->idFieldName)
				continue;
			
			if (isset($data[$fieldName]))
				$value = $data[$fieldName];
			else
			if (isset($this->itemData[$fieldName]))
				$value = $this->itemData[$fieldName];
			else
			if (isset($fieldData["defaultValue"])) {

				switch ($fieldData["defaultValue"]) {
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE:
						$value = $fieldData["value"];
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_DATE:
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_DATETIME:
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_TIMESTAMP:
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_TIME:
						$value = time();
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_YEAR:
						$value = date("Y");
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_IP:
						$value = $this->getClientIp();
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_DEFAULT_VALUE_AVAILABLE_URL_SHORT_CODE:
						$value = $this->getRandomAvailableUrlShortCode($fieldName);
						break;
				}

			}
			else
				continue;
			
			if (isset($fieldData["isMultiLanguage"]) && $fieldData["isMultiLanguage"]) { // If this field is multilanguage
				if (is_array($value)) { // If we have an array value (expected to be a <language code> => <value> hash array)
					foreach ($e->Locale->getConfig("availableLanguages") as $language) {
						$fieldsData[$fieldName."_".$e->Locale->getLanguageCode($language)] = $value[$language];
					}
				}
				else { // If we have a value that's not an array, assign it to the currently detected language
					$fieldsData[$fieldName."_".$e->Locale->getLanguageCode()] = $value;
				}
			}
			else { // If the field is not multilanguage
				$fieldsData[$fieldName] = [
					"type" => $fieldData["type"],
					"value" => $value
				];
			}

			$data[$fieldName] = $value;
		}
		reset($this->fields);

		if (!$result = $e->Database->{$this->databaseProviderName}->insert($this->tableName, $fieldsData))
			return false;
		
		if ($this->idFieldName)
			$data[$this->idFieldName] = $result->getInsertId();

		$this->loadFromData($data);

		$this->clearCache();

		return true;
	}

	/**
	 * @return string The client's IP
	 */
	function getClientIp() {
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			return $_SERVER["HTTP_X_FORWARDED_FOR"];
		else
			return $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * Finds a random available url short code
	 * 
	 * @var string $fieldName The name of the field that holds url short codes for this item
	 */
	function getRandomAvailableUrlShortCode($fieldName, $numberOfCharacters = false) {
		if (!$numberOfCharacters)
			$numberOfCharacters = $this->minUrlShortCodeCharacters;

		$tries = 0;

		while (true) {

			$code = "";
			for ($i = 0; $i < $numberOfCharacters; $i ++)
				$code .= substr($this->urlShortCodeCharacters, rand(0, strlen($this->urlShortCodeCharacters) - 1), 1);
			
			if ($this->isAvailableUrlShortCode($fieldName, $code))
				return $code;

			$tries ++;

			if ($tries >= $this->maxUrlShortCodeTriesForCodeLength)
				return $this->getRandomAvailableUrlShortCode($fieldName, ++$numberOfCharacters);
			
		}

		return $code;
	}

	/**
	 * Checks whether a given url short code for the given field is available to be used or not (i.e: It's being used by another item or not)
	 * @param string $fieldName The field name that is being used to store codes for this Item
	 * @param string $code The code to check
	 * @return boolean True if the code is available to be used, false otherwise
	 */
	function isAvailableUrlShortCode($fieldName, $code) {
		global $e;

		if (!$result = $e->Database->{$this->databaseProviderName}->prepareAndExecute(
			"select ".$this->idFieldName." from ".$this->tableName." where ".$fieldName." = ?",
			[
				[
					"type" => $this->fields[$fieldName]["type"],
					"value" => $code
				]
			]
		))
			return false;

		return true;
	}

	/**
	 * Creates a new item with the given setup and data. This method can be overriden when implementing specific items when additional tasks need to be done before/after inserting an item into de database, passing additional $setup keys if needed. If not overriden, it simply adds the row on the database.
	 * Setup keys:
	 * * data: A hash array of the data for the new item, where each key matches the field name on the database table. Fields must be defined on this->fields
	 * @param array $setup Hash array of setup options
	 * @return boolean True if creation went ok, false otherwise
	 */
	function create($setup = false) {
		return $this->insert($setup["data"]);
	}

	/**
	 * Updates the data on the database for this Item
	 * @param array $data A hash array of the keys and values to update
	 * For multilanguage fields, a hash array with the syntax [<language code> => <value>, ...] can be passed. If a non-array value is passed the currently detected language will be used
	 * @return boolean True if everything went ok, false otherwise
	 */
	function update($data) {
		global $e;

		if (!$this->idFieldName) {
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Couldn't update item on the database because it hasn't an idFieldName set up.",
				"errorVariables" => [
					"Item class" => get_class($this)
				]
			]);
			return false;
		}

		while (list($fieldName, $fieldData) = each($data)) {
			if ($this->fields[$fieldName]["isMultiLanguage"]) {
				global $e;
				if (is_array($fieldData)) {
					
					foreach ($e->Locale->getConfig("availableLanguages") as $language) {

						$this->{$fieldName."_".$e->Locale->getLanguageCode($language)} = $fieldData[$language];
						$fields[$fieldName."_".$e->Locale->getLanguageCode($language)] = [
							"type" => $this->fields[$fieldName]["type"],
							"value" => $fieldData[$language]
						];

					}

				}
				else {

					$this->$fieldName = $fieldData;
					$fields[$fieldName."_".$e->Locale->getLanguageCode()] = [
						"type" => $this->fields[$fieldName]["type"],
						"value" => $fieldData
					];

				}
			}
			else {

				$this->$fieldName = $fieldData;
				$fields[$fieldName] = [
					"type" => $this->fields[$fieldName]["type"],
					"value" => $fieldData
				];

			}
		}

		return $e->Database->{$this->databaseProviderName}->updateByUniqueField(
			$this->tableName,
			$this->idFieldName,
			$this->{$this->idFieldName},
			$fields
		);
	}

	/**
	 * Deletes the data on the database representing this unique item, as per the current $idFieldName value
	 * @return boolean True on success, false on failure
	 */
	function delete() {
		global $e;

		if (!$this->idFieldName) {
			$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, [
				"errorDescription" => "Couldn't delete item from the database because it hasn't an idFieldName set up.",
				"errorVariables" => [
					"Item class" => get_class($this)
				]
			]);
			return false;
		}

		if (!$e->Database->{$this->databaseProviderName}->deleteByUniqueField(
			$this->tableName,
			$this->idFieldName,
			$this->{$this->idFieldName}
		))
			return false;

		return $this->clearCache();
	}

	/**
	 * init
	 *
	 * Initializes the Item. Intended to be overloaded to perform any additional actions that must be done just after the Item is loaded with data
	 *
	 * @return boolean True on success, false on failure
	 */
	function init() {
		return true;
	}

	/**
	 * Magic get method to return the Item's data corresponding to the specified $key
	 * If the key is for a database field that is language dependant as specified by $this->fields, the proper language data according to the current Locale language will be returned
	 * If the key is for a timezone dependant field as specified by $this->fields, the proper timezone adjusted timestamp will be returned according to the current Locale timezone
	 * 
	 * @param string $key The key of the data to get
	 * @return mixed The data. Null if data with the given key is not set.
	 */
	function __get($key) {
		// If key is for a database field
		if (isset($this->fields) && isset($this->fields[$key])) {
			// If it's a language dependant field
			if (isset($this->fields[$key]["isMultiLanguage"])) {
				global $e;
				$key .= "_".$e->Locale->getLanguageCode();
			}
		}

		if (property_exists($this, $key))
			return $this->$key;

		if (!is_array($this->itemData))
			return null;

		if (!array_key_exists($key, $this->itemData))
			return null;

		return $this->itemData[$key];
	}

	/**
	 * Gets the item data for the specificied language when the field is language dependant
	 * @param string $key The key of the data to get
	 * @param integer $language The language to get the data for
	 * @return mixed The data. Null if data with the given key is not set, or false if the specified key is not for a language dependant field
	 */
	function getForLanguage($key, $language) {
		if (!$this->fields || $this->fields[$key]["isMultiLanguage"])
			return false;
		$key .= "_".$e->Locale->getLanguageCode($language);
		return $this->$key;
	}

	/**
	 * Gets the item data for the specified timezone when the field is timezone dependant
	 * @param string $key The key of the data to get
	 * @param string $timeZone The timezone, as in http://www.php.net/timezones. If none specified, the current Locale timezone is used.
	 * @return integer The timestamp data. Null if data with the given key is not set, or false if the specified key is not for a timezone dependant field
	 */
	function getForTimezone($key, $timeZone = false) {
		if (!$this->fields || (
			$this->fields[$key]["type"] !== \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME
			&&
			$this->fields[$key]["type"] !== \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIME
		))
			return false;

		global $e;
		if (!$value = $this->$key)
			return $value;
		return $e->Locale->convertTimestamp($value, $timeZone);
	}

	/**
	 * Gets the specified item data in a way that's readable for a human.
	 * Intended to be used by modules like UiComponentTable
	 * @param string $key The key of the data to get
	 * @param array $setup A hash array of additional options, amongst the following keys:
	 * * isHtml: Whether to use HTML code to humanize when suitable or not. Defaults to true.
	 * * isEmoji: Whether to use Emoji to humanize when suitable or not. Defaults to true.
	 * * isUiComponentIcons: Whether to return UiComponentIcons HTML code to humanize when suitale or not. Defaults to false.
	 * * emojiBooleanTrue: The emoji to use to represent true boolean values. Defaults to "✅"
	 * * emojiBooleanFalse: The emoji to use to represent false boolean values. Defaults to "❌"
	 * * emojiEmpty The emoji to use to represent empty values. Defaults to "❌"
	 * * iconVariant: The variant to use when using UiComponentIcons to humanize. Defaults to black.
	 * * iconNameBooleanTrue: The icon name to use as boolean true when representing boolean values with icons. Defaults to "true"
	 * * iconNameBooleanTrue: The icon name to use as boolean false when representing boolean values with icons. Defaults to "false"
	 * * iconNameEmpty: The icon name to represent empty values. Defaults to "empty".
	 * * iconVariantEmpty: The icon variant to represent empty values. Defaults to "grey".
	 * @param boolean $isHtml Whehter to use HTML to help make the data readable by a human
	 * @return string The HTML representing 
	 */
	function getHumanized($key, $setup = false) {
		global $e;

		self::treatParameters($setup, [
			"isHtml" => ["default" => true],
			"isEmoji" => ["default" => true],
			"isUiComponentIcons" => ["default" => false],
			"emojiBooleanTrue" => ["default" => "✅"],
			"emojiBooleanFalse" => ["default" => "❌"],
			"emojiEmpty" => ["default" => "❌"],
			"iconVariant" => ["default" => "black"],
			"iconNameBooleanTrue" => ["default" => "true"],
			"iconNameBooleanFalse" => ["default" => "false"],
			"iconNameEmpty" => ["default" => "empty"],
			"iconVariantEmpty" => ["default" => "lightGrey"]
		]);

		$r = $this->{$key};

		if ($this->fields[$key]["humanizeMethodName"]) {
			$finalR = $this->{$this->fields[$key]["humanizeMethodName"]}($this);
			if (!is_null($finalR))
				return $finalR;
		}

		if ($this->fields[$key]["humanizePreMethodName"])
			$r = $this->{$this->fields[$key]["humanizePreMethodName"]}($this);

		if ($setup["isUiComponentIcons"]) {
			$rEmpty =
				"<div class=\"".
					"UiComponentIcon".
					" ".$setup["iconNameEmpty"].
					" ".$setup["iconVariantEmpty"].
				"\"></div>";
			$rBooleanTrue =
				"<div class=\"".
					"UiComponentIcon".
					" ".$setup["iconNameBooleanTrue"].
					" ".$setup["iconVariant"].
				"\"></div>";
			$rBooleanFalse =
				"<div class=\"".
					"UiComponentIcon".
					" ".$setup["iconNameBooleanFalse"].
					" ".$setup["iconVariant"].
				"\"></div>";
		}
		else
		if ($setup["isEmoji"]) {
			$rEmpty = $setup["emojiEmpty"];
			$rBooleanTrue = $setup["emojiBooleanTrue"];
			$rBooleanFalse = $setup["emojiBooleanFalse"];
		}
		else
		if ($setup["isHtml"]) {
			$rEmpty = "&#10007;";
			$rBooleanTrue = "&#10003;";
			$rBooleanFalse = "&#10007;";
		}
		else {
			$rEmpty = "-";
			$BooleanTrue = "Y";
			$BooleanFalse = "N";
		}

		switch ($this->fields[$key]["type"]) {
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_INTEGER:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TINYINT:
				$r = $e->Locale->formatNumber(
					$r,
					[
						"decimals" => $this->fields[$key]["decimals"],
						"decimalMark" => $this->fields[$key]["decimalMark"],
						"isSeparateThousands" => $this->fields[$key]["isSeparateThousands"],
						"multiplier" => $this->fields[$key]["multiplier"]
					]
				);
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_FLOAT:
				$r = $e->Locale->formatNumber(
					$r,
					[
						"decimals" => $this->fields[$key]["decimals"],
						"decimalMark" => $this->fields[$key]["decimalMark"],
						"isSeparateThousands" => $this->fields[$key]["isSeparateThousands"],
						"multiplier" => $this->fields[$key]["multiplier"]
					]
				);
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATE:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIMESTAMP:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIME:

				if (!$r) {
					$r = $rEmpty;
					break;
				}

				switch ($this->fields[$key]["type"]) {
					case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATE:
						$r = $e->Locale->formatTimestamp(
							$r,
							[
								"isHours" => false,
								"isSeconds" => false
							]
						);
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_TYPE_DATETIME:
					case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIMESTAMP:
						$r = $e->Locale->formatTimestamp(
							$r,
							[
								"isHours" => true,
								"isSeconds" => true
							]
						);
						break;
					case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TIME:
						$r = $e->Locale->formatTimestamp(
							$r,
							[
								"isDay" => false,
								"isHours" => true,
								"isSeconds" => true
							]
						);
						break;
				}

				break;
			
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_YEAR:
				$r = $e->Locale->formatTimestamp(
					$r,
					[
						"format" => "Y"
					]
				);
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_BOOLEAN:
				$r = $r ? $rBooleanTrue : $rBooleanFalse;
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_IP:
				if (!$r) {
					$r = $rEmpty;
					break;
				}
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_SERIALIZED:
				$value = $r;
				
				if (!$value) {
					$r = false;
					break;
				}

				if (!$setup["isHtml"]) {
					$r = print_r($value, true);
					break;
				}

				$table = "<table class=\"panel\">";
					while (list($k1, $v1) = each($value)) 
						$table .=
							"<tr>".
								"<td>".$k1."</td>".
								"<td style=\"text-align: right;\">".
									(is_array($v1) ? print_r($v1, true) : $v1).
								"</td>".
							"</tr>";
				$table .= "</table>";
				$r = $table;
				break;
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_STRING:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_TEXT:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_BLOB:
			case \Cherrycake\Modules\DATABASE_FIELD_TYPE_COLOR:
			default:
				if (!$r) {
					$r = $rEmpty;
					break;
				}
				break;
		}
		
		if ($this->fields[$key]["prefix"])
			$r = $this->fields[$key]["prefix"].$r;

		if ($this->fields[$key]["postfix"])
			$r = $r.$this->fields[$key]["postfix"];

		if ($this->fields[$key]["humanizePostMethodName"])
			$r = $this->{$this->fields[$key]["humanizePostMethodName"]}($r, $this);

		return $r;
	}

	/**
	 * Magic set method to set the data $key to the given $value
	 * @param string $key The key of the data to set
	 * @param mixed $value The value
	 */
	function __set($key, $value) {
		if (property_exists($this, $key)) {
			$this->$key = $value;
			return;
		}

		$this->itemData[$key] = $value;
	}

	/**
	 * Magic method to check if the data with the given $key is set
	 * @param string $key The key of the data to check
	 * @param boolean True if the data exists, false otherwise
	 */
	function __isset($key) {
		return isset($this->itemData[$key]);
	}
}