<?php

/**
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * A class that implements some basic functionalities intended to make it easier to instantiate and use of classes implementing it. Provides an easy way to access object properties, an object factory, a constructor, an advanced parameters manager ...
 *
 * @package Cherrycake
 * @category Classes
 */
class BasicObject {
	/**
	 * Creates an object and returns it
	 * 
	 * @param array $setup A hash array with the wanted specs
	 * @return BasicObject The object
	 */
	static function build($properties = false) {
		try {
			$className = get_called_class();
			$object = new $className($properties);
		} catch (\Exception $exception) {
			return false;
		}
		return $object;
	}

	/**
	 * @param array $properties A hash array with the setup keys
	 */
	function __construct($properties = false) {
		$this->setProperties($properties);
	}

	/**
	 * Bulk sets the given properties for this object
	 * @param array $properties A hash array of properties
	 * @param boolean $isOverwrite Whether to overwrite properties if already set
	 */
	function setProperties($properties, $isOverwrite = true) {
		if (is_array($properties))
			foreach ($properties as $key => $value)
				if (!is_null($value))
					if ((isset($this->$key) && $isOverwrite) || !isset($this->$key))
						$this->$key = $value;
	}

	/**
	 * Provides a system to pass complex parameters to a method, where parameters are passed in a hash array instead of the usual parameters passing method. A method using this system should accept a hash array of parameters and pass them as the first parameter to this method via a parent::treatParameters call, specifying as a second parameter a setup hash array of options for each parameter to be treated in a special way.
	 * @param array &$parameters The hash array of received parameters
	 * @param array $setup A hash array of setup options on how to treat each passed parameter
	 */
	function treatParameters(&$parameters, $setup) {
		if (!is_array($setup))
			return;

		while (list($parameterName, $parameterSetup) = each($setup)) {
			if (!isset($parameters[$parameterName])) {
				if ($parameterSetup["isRequired"]) {
					global $e;
					$e->loadCoreModule("Errors");
					$e->Errors->trigger(\Cherrycake\Modules\ERROR_SYSTEM, ["errorDescription" => "Parameter \"".$parameterName."\" is required when calling ".debug_backtrace()[1]["class"]."::".debug_backtrace()[1]["function"]]);
				}
				if (isset($parameterSetup["default"]))
					$parameters[$parameterName] = $parameterSetup["default"];
			}

			if (isset($parameterSetup["addArrayKeysIfNotExist"])) {
				if (!$parameters[$parameterName] && !is_array($parameters[$parameterName]))
					$parameters[$parameterName] = [];
				$parameters[$parameterName] = array_replace($parameters[$parameterName], $parameterSetup["addArrayKeysIfNotExist"]);
			}

			if (isset($parameterSetup["addArrayValuesIfNotExist"])) {
				foreach ($parameterSetup["addArrayValuesIfNotExist"] as $addArrayValueIfNotExist)
					if (is_array($parameters[$parameterName])) {
						if (!in_array($addArrayValueIfNotExist, $parameters[$parameterName]))
							$parameters[$parameterName][] = $addArrayValueIfNotExist;
					}
					else
						$parameters[$parameterName][] = $addArrayValueIfNotExist;
			}
		}
		reset($setup);
	}
}