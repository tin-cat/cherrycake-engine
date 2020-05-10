<?php

/**
 * Request
 *
 * @package Cherrycake
 */

namespace Cherrycake;

/**
 * Request
 *
 * A class that represents a request to the engine, mainly used via an Action mapped into Actions module.
 *
 * @package Cherrycake
 * @category Classes
 */
class Request {
	/**
	 * @var array $pathComponents An array of RequestPathComponent objects defining the components of this request, in the same order on which they're expected
	 */
	public $pathComponents;

	/**
	 * @var array $parameters An array of RequestParameter objects of parameters that might be received by this request
	 */
	public $parameters;

	/**
	 * @var array $parameterValues A two-dimensional array of retrieved parameters for this request, filled by retrieveParameterValues()
	 */
	private $parameterValues;

	/**
	 * @var boolean Whether this Request should perform checks aimed to mitigate CSRF attacks, like adding a per-user unique token to requests and checking it agains the token stored on the user's session, or checking for a matching domain on the request coming from the user's browser against the current domain.
	 */
	public $isSecurityCsrf;

	/**
	 * @var array $additionalCacheKeys A hash array containing additional cache keys to make this request's cached contents different depending on the values of those keys
	 */
	private $additionalCacheKeys;

	/**
	 * Request
	 *
	 * Constructor factory
	 *
	 * @param string $with How to populate the created Request object. Leave to false for unpopulated request.
	 */
	function __construct($setup = false) {
		$this->isSecurityCsrf = $setup["isSecurityCsrf"] ?? false;

		if ($this->isSecurityCsrf()) {			
			global $e;
			$setup["parameters"][] = new \Cherrycake\RequestParameter([
				"name" => "csrfToken",
				"type" => \Cherrycake\REQUEST_PARAMETER_TYPE_GET,
				"value" => $e->Security->getCsrfToken()
			]);
		}

		$this->pathComponents = isset($setup["pathComponents"]) ? $setup["pathComponents"] : false;
		$this->parameters = isset($setup["parameters"]) ? $setup["parameters"] : false;
		$this->additionalCacheKeys = isset($setup["additionalCacheKeys"]) ? $setup["additionalCacheKeys"] : false;
	}

	/*
	 * isCurrentRequest
	 *
	 * Checks whether this request matches the current one made
	 *
	 * @return bool True if this request matches the current one made, false if not.
	 */
	function isCurrentRequest() {
		global $e;

		if (!is_array($e->Actions->currentRequestPathComponentStrings)) { // If the current request doesn't has pathComponents

			if (!is_array($this->pathComponents)) // If this request doesn't have pathComponents, this is the current Request
				return true;
			else
				return false; // If his request has pathComponents, this is not the current Request

		}
		else { // Else the current request has pathComponents
			if (!is_array($this->pathComponents)) { // If this request doesn't have pathComponents, this is not the current Request
				return false;
			} else { // Else this request has pathComponents, further analysis must be done

				if (sizeof($this->pathComponents) != sizeof($e->Actions->currentRequestPathComponentStrings)) // If the number of this Request's pathComponents is different than the number of the current request's pathComponents, this is not the current Request
					return false;

				$isCurrentRequest = true;
				// Loop in parallel through the current request path components and this request's path components
				foreach ($this->pathComponents as $index => $pathComponent) {
					if (!isset($e->Actions->currentRequestPathComponentStrings[$index])) {
						$isCurrentRequest = false;
						break;
					}

					if (!$pathComponent->isMatchesString($e->Actions->currentRequestPathComponentStrings[$index])) {
						$isCurrentRequest = false;
						break;
					}
				}
				reset($e->Actions->currentRequestPathComponentStrings);
				reset($this->pathComponents);
				return $isCurrentRequest;
			}

		}
	}

	/**
	 * retrieveParameterValues
	 *
	 * Retrieves all the parameters bonded to this Request, coming either from path component strings, get or post. It also performs security checks on them when needed
	 *
	 * @return bool True if all the parameters have been retrieved correctly and no security issues found, false otherwise
	 */
	function retrieveParameterValues() {
		global $e;

		// Retrieve parameters coming from path components
		$isErrors = false;
		if (is_array($this->pathComponents)) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				if(
					$pathComponent->type == REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING
					||
					$pathComponent->type == REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC
				) {
					$this->pathComponents[$index]->setValue($e->Actions->currentRequestPathComponentStrings[$index]);
					$result = $pathComponent->checkValueSecurity();
					if (!$result->isOk) {
						$isErrors = true;
						$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
							"errorDescription" => implode(" / ", $result->description),
							"errorVariables" => [
								"pathComponent name" => $pathComponent->name,
								"pathComponent value" => $pathComponent->getValue()
							]
						]);
					}
					else
						$this->parameterValues[$pathComponent->name] = $pathComponent->getValue();
				}
			}
			reset($this->pathComponents);
		}

		// Retrieve parameters coming from get or post
		if (is_array($this->parameters)) {
			foreach ($this->parameters as $parameter) {
				$parameter->retrieveValue();
				$result = $parameter->checkValueSecurity();
				if (!$result->isOk) {
					$isErrors = true;
					$e->Errors->trigger(\Cherrycake\ERROR_SYSTEM, [
						"errorDescription" => implode(" / ", $result->description),
						"errorVariables" => [
							"parameter name" => $parameter->name,
							"parameter value" => $parameter->getValue()
						]
					]);
				}
				else {
					if ($parameter->isReceived())
						$this->parameterValues[$parameter->name] = $parameter->getValue();
				}
			}
			reset($this->parameters);
		}

		return !$isErrors;
	}

	/**
	 * Should be called after retrieveParameterValues
	 * @param string $name The name of the parameter to check
	 * @return boolean Whether the specified parameter $name has been passed or not
	 */
	function isParameterReceived($name) {
		return isset($this->parameterValues[$name]);
	}

	/**
	 * Gets the value retrieved for a specific parameter for this request. retrieveParameterValues() must be called before.
	 * @param string $name The name of the parameter to get
	 * @return mixed The value of the parameter, false if it doesn't exists
	 */
	function getParameterValue($name) {
		if (!isset($this->parameterValues[$name]))
			return false;
		return $this->parameterValues[$name];
	}

	/**
	 * Magic get method to return the retrieved value for a specific parameter for this request. retrieveParameterValues() must be called before.
	 * @param string $name The name of the parameter
	 * @return mixed The data. Null if data with the given key is not set.
	 */
	function __get($name) {
		return $this->getParameterValue($name);
	}

	/**
	 * Magic method to check if the specified parameter has been passed or not
	 * @param string $name The name of the parameter
	 * @param boolean True if the data parameter has been passed, false otherwise
	 */
	function __isset($name) {
		return $this->isParameterReceived($name);
	}

	/**
	 * @return boolean Whether this request must implement security against Csrf attacks
	 */
	function isSecurityCsrf() {
		return $this->isSecurityCsrf;
	}

	/**
	 * Returns a URL that represents a call to this request, including the given path components and parameter values
	 *
	 * @param array $setup An option setup two-dimensional array containing:
	 * * parameterValues: An optional hash array containing the values for the variable path components and for the GET parameters, if any. (not additionalCacheKeys, since they're not represented on the Url itself).
	 * * isIncludeUrlParameters: Includes the GET parameters in the URL. The passed parameterValues will be used, or the current request's parameters if no parameterValues are specified. Defaults to true.
	 * * isAbsolute: Whether to generate an absolute url containing additionally http(s):// and the domain of the App. Defaults to false
	 * * isHttps: Whether to generate an https url or not, with the following possible values:
	 *  - true: Use https://
	 *  - false: Use http://
	 *  - "auto": Use https:// if the current request has been made over https, http:// otherwise
	 * @return string The Url
	 */
	function buildUrl($setup = false) {
		if (!isset($setup["isIncludeUrlParameters"]))
			$setup["isIncludeUrlParameters"] = true;

		if (!isset($setup["isAbsolute"]))
			$setup["isAbsolute"] = false;

		if (!isset($setup["isHttps"]))
			$setup["isHttps"] = false;

		if (!isset($setup["parameterValues"]) && $setup["isIncludeUrlParameters"])
			$this->retrieveParameterValues();

		if ($setup["isAbsolute"]) {
			if ($setup["isHttps"] === false)
				$url = "http://";
			else
			if ($setup["isHttps"] === true)
				$url = "https://";
			else
			if ($setup["isHttps"] == "auto") {
				if ($_SERVER["HTTPS"])
					$url = "https://";
				else
					$url = "http://";
			}
			$url .= $_SERVER["SERVER_NAME"];
		}
		else
			$url = "";

		if (is_array($this->pathComponents)) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				switch ($pathComponent->type) {
					case REQUEST_PATH_COMPONENT_TYPE_FIXED:
						$url .= "/".$pathComponent->string;
						break;
					case REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
					case REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
						if ($setup["parameterValues"])
							$url .= "/".$setup["parameterValues"][$pathComponent->name];
						else
							$url .= "/".$this->{$pathComponent->name};
						break;
				}
			}
			reset($this->pathComponents);
		}
		else
			$url .= "/";

		$count = 0;
		if (is_array($this->parameters) && $setup["isIncludeUrlParameters"]) {			
			foreach ($this->parameters as $parameter) {
				if ($setup["parameterValues"] ?? false) {
					if ($setup["parameterValues"][$parameter->name] ?? false)
						$url .= (!$count++ ? "?" : "&").$parameter->name."=".$setup["parameterValues"][$parameter->name];
				}
				else
					if ($this->{$parameter->name})
						$url .= (!$count++ ? "?" : "&").$parameter->name."=".$this->{$parameter->name};
			}
		}

		// if ($this->isSecurityCsrf()) {
		// 	global $e;
		// 	$url .= ($count > 0 ? "&" : "?")."csrfToken=".$e->Security->getCsrfToken();
		// }

		if (isset($setup["anchor"]))
			$url .= "#".$setup["anchor"];

		return $url;
	}

	/**
	 * Returns an HTML form that will call this request when submitted
	 * @param array $setup As in UiComponentform::build
	 * @return string The HTML
	 */
	function buildFormHtml($setup = false) {
		global $e;
		$setup["request"] = $this;
		return $e->UiComponentForm->build($setup);
	}

	/**
	 * getCacheKey
	 *
	 * @param string $prefix The prefix to use for the cache key
	 * @param array $parameterValues An optional two-dimensional array containing values for all the parameters related to this request, including url path parameters, get/post parameters and additionalCacheKeys. If not specified, the current retrieved values will be used
	 * @return string A string that represents uniquely this request, to be used as a cache key
	 */
	function getCacheKey($prefix, $parameterValues = null) {
		$key = "";
		if (is_array($this->pathComponents)) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				switch ($pathComponent->type) {
					case REQUEST_PATH_COMPONENT_TYPE_FIXED:
						$key .= "_".$pathComponent->string;
						break;
					case REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
					case REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
						if (is_array($parameterValues))
							$key .= "_".$parameterValues[$pathComponent->name];
						else
							$key .= "_".$pathComponent->getValue();
						break;
				}
			}
			reset($this->pathComponents);
		}
		else
			$key = "_";

		if (is_array($this->parameters)) {
			foreach ($this->parameters as $parameter) {
				if (isset($parameterValues))
					$key .= "_".$parameter->name."=".$parameterValues[$parameter->name];
				else
					$key .= "_".$parameter->name."=".$this->{$parameter->name};

			}
			reset($this->parameters);
		}

		if (is_array($this->additionalCacheKeys)) {
			while (list($additionalCacheKey, $value) = each($this->additionalCacheKeys)) {
				if (isset($parameterValues))
					$key .= "_".$additionalCacheKey."=".$parameterValues[$key];
				else
					$key .= "_".$additionalCacheKey."=".$value;

			}
			reset($this->additionalCacheKeys);
		}

		$key = substr($key, 1);

		global $e;
		$cacheKeyNamingOptions["prefix"] = $prefix;
		$cacheKeyNamingOptions["key"] = $key;
		return \Cherrycake\Cache::buildCacheKey($cacheKeyNamingOptions);
	}

	/**
	 * Checks this request for security problems
	 * @return boolean True if no issues found during checking, false otherwise.
	 */
	function securityCheck() {
		global $e;
		return $e->Security->checkRequest($this);
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		$r["brief"] = "";
		if (is_array($this->pathComponents)) {
			foreach ($this->pathComponents as $pathComponent) {
				$pathComponentsStatus[] = $pathComponent->getStatus()["brief"];
				$r["pathComponents"][] = $pathComponent->getStatus();
			}
			reset($this->pathComponents);
			$r["brief"] .= implode("/", $pathComponentsStatus);
		}
		else
			$r["brief"] .= "/";

		if ($this->parameters) {
			$r["brief"] .= " ";
			foreach ($this->parameters as $parameter) {
				$parametersStatus[] = $parameter->getStatus()["brief"];
				$r["parameters"][] = $parameter->getStatus();
			}
			$r["brief"] .= "(".implode(" ", $parametersStatus).")";
			reset($this->parameters);
		}
		
		if (is_array($this->additionalCacheKeys))
			$r["additionalCacheKeys"] = $this->additionalCacheKeys;

		return $r;
	}
}