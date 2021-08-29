<?php

namespace Cherrycake\Actions;

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
	 * @var array $parameterValues A two-dimensional array of retrieved parameters for this request, filled by retrieveParameterValues()
	 */
	private $parameterValues;

	/**
	 * @param array $pathComponents An array of RequestPathComponent objects defining the components of this request, in the same order on which they're expected
	 * @param array $parameters An array of RequestParameter objects of parameters that might be received by this request
	 * @param array $additionalCacheKeys A hash array containing additional cache keys to make this request's cached contents different depending on the values of those keys
	 */
	function __construct(
		public array $pathComponents = [],
		public array $parameters = [],
		public bool $isSecurityCsrf = false,
		private array $additionalCacheKeys = [],

	) {
		if ($this->isSecurityCsrf()) {
			global $e;
			$setup["parameters"][] = new \Cherrycake\Actions\RequestParameter(
				name: "csrfToken",
				type: \Cherrycake\REQUEST_PARAMETER_TYPE_GET,
				value: $e->Security->getCsrfToken()
			);
		}
	}

	/*
	 * Checks whether this request matches the current one made
	 * @return bool True if this request matches the current one made, false if not.
	 */
	function isCurrentRequest():bool {
		global $e;

		if (!$e->Actions->currentRequestPathComponentStrings) { // If the current request doesn't has pathComponents

			if (!$this->pathComponents) // If this request doesn't have pathComponents, this is the current Request
				return true;
			else
				return false; // If his request has pathComponents, this is not the current Request

		}
		else { // Else the current request has pathComponents
			if (!$this->pathComponents) { // If this request doesn't have pathComponents, this is not the current Request
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
	 * Retrieves all the parameters bonded to this Request, coming either from path component strings, get or post. It also performs security checks on them when needed
	 * @return bool True if all the parameters have been retrieved correctly and no security issues found, false otherwise
	 */
	function retrieveParameterValues(): bool {
		global $e;

		// Retrieve parameters coming from path components
		$isErrors = false;
		if ($this->pathComponents) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				if(
					$pathComponent->type == \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING
					||
					$pathComponent->type == \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC
				) {
					$this->pathComponents[$index]->setValue($e->Actions->currentRequestPathComponentStrings[$index]);
					$result = $pathComponent->checkValueSecurity();
					if (!$result->isOk) {
						$isErrors = true;
						$e->Errors->trigger(
							type: \Cherrycake\ERROR_SYSTEM,
							description: implode(" / ", $result->description),
							variables: [
								"pathComponent name" => $pathComponent->name,
								"pathComponent value" => $pathComponent->getValue()
							]
						);
					}
					else
						$this->parameterValues[$pathComponent->name] = $pathComponent->getValue();
				}
			}
			reset($this->pathComponents);
		}

		// Retrieve parameters coming from get or post
		if ($this->parameters) {
			foreach ($this->parameters as $parameter) {
				$parameter->retrieveValue();
				$result = $parameter->checkValueSecurity();
				if (!$result->isOk) {
					$isErrors = true;
					$e->Errors->trigger(
						type: \Cherrycake\ERROR_SYSTEM,
						description: implode(" / ", $result->description),
						variables: [
							"parameter name" => $parameter->name,
							"parameter value" => $parameter->getValue()
						]
					);
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
	function isParameterReceived(string $name): bool {
		return isset($this->parameterValues[$name]);
	}

	/**
	 * Gets the value retrieved for a specific parameter for this request. retrieveParameterValues() must be called before.
	 * @param string $name The name of the parameter to get
	 * @return mixed The value of the parameter, false if it doesn't exists
	 */
	function getParameterValue(string $name): mixed {
		if (!isset($this->parameterValues[$name]))
			return false;
		return $this->parameterValues[$name];
	}

	/**
	 * Magic get method to return the retrieved value for a specific parameter for this request. retrieveParameterValues() must be called before.
	 * @param string $name The name of the parameter
	 * @return mixed The data. Null if data with the given key is not set.
	 */
	function __get(string $name): mixed {
		return $this->getParameterValue($name);
	}

	/**
	 * Magic method to check if the specified parameter has been passed or not
	 * @param string $name The name of the parameter
	 * @param boolean True if the data parameter has been passed, false otherwise
	 */
	function __isset(string $name): bool {
		return $this->isParameterReceived($name);
	}

	/**
	 * @return boolean Whether this request must implement security against Csrf attacks
	 */
	function isSecurityCsrf(): bool {
		return $this->isSecurityCsrf;
	}

	/**
	 * Returns a URL that represents a call to this request, including the given path components and parameter values
	 * @param string $locale An optional string indicating the locale name for which to build the Url. If not specified, the current locale's domain will be used when isAbsolute is true. When specified, returned Url will be absolute.
	 * @param array $parameterValues An optional hash array containing the values for the variable path components and for the GET parameters, if any. (not additionalCacheKeys, since they're not represented on the Url itself).
	 * @param bool $isIncludeUrlParameters Includes the GET parameters in the URL. The passed parameterValues will be used, or the current request's parameters if no parameterValues are specified. Defaults to true.
	 * @param bool $isAbsolute Whether to generate an absolute url containing additionally http(s):// and the domain of the App. Defaults to false
	 * @param bool|string $isHttps Whether to generate an https url or not, with the following possible values:
	 *  - true: Use https://
	 *  - false: Use http://
	 *  - "auto": Use https:// if the current request has been made over https, http:// otherwise
	 * @return string The Url
	 */
	function buildUrl(
		string $locale = '',
		array $parameterValues = [],
		bool $isIncludeUrlParameters = true,
		bool $isAbsolute = false,
		bool|string $isHttps = 'auto'
	): string {
		if ($isHttps === 'auto')
			$setup["isHttps"] = $_SERVER["HTTPS"] ?? false ? true : false;

		if (!isset($parameterValues) && $isIncludeUrlParameters)
			$this->retrieveParameterValues();

		if ($isAbsolute) {
			if ($isHttps === false)
				$url = "http://";
			else
			if ($isHttps === true)
				$url = "https://";
			else
			if ($isHttps == "auto") {
				if ($_SERVER["HTTPS"] ?? false)
					$url = "https://";
				else
					$url = "http://";
			}

			// Determine the domain
			// If we haven't a forced locale, use the current domain
			if (!$locale)
				$url .= $_SERVER["HTTP_HOST"];
			else {
				// If we have a forced locale, use its domain. Requires the Locale module to be available.
				global $e;
				$e->loadAppModule("Locale");
				$url .= $e->Locale->getMainDomain($locale);
			}
		}
		else
			$url = "";

		if ($this->pathComponents) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				switch ($pathComponent->type) {
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED:
						$url .= "/".$pathComponent->string;
						break;
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
						if ($parameterValues ?? false)
							$url .= "/".$parameterValues[$pathComponent->name];
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
		if ($this->parameters && $isIncludeUrlParameters) {
			foreach ($this->parameters as $parameter) {
				if ($parameterValues ?? false) {
					if ($parameterValues[$parameter->name] ?? false)
						$url .= (!$count++ ? "?" : "&").$parameter->name."=".$parameterValues[$parameter->name];
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

		if (isset($anchor))
			$url .= "#".$anchor;

		return $url;
	}

	/**
	 * @param string $prefix The prefix to use for the cache key
	 * @param array $parameterValues An optional two-dimensional array containing values for all the parameters related to this request, including url path parameters, get/post parameters and additionalCacheKeys. If not specified, the current retrieved values will be used
	 * @return string A string that represents uniquely this request, to be used as a cache key
	 */
	function getCacheKey(string $prefix, array $parameterValues = null): string {
		$key = "";
		if ($this->pathComponents) {
			foreach ($this->pathComponents as $index => $pathComponent) {
				switch ($pathComponent->type) {
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED:
						$key .= "_".$pathComponent->string;
						break;
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
					case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
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

		if ($this->parameters) {
			foreach ($this->parameters as $parameter) {
				if (isset($parameterValues))
					$key .= "_".$parameter->name."=".$parameterValues[$parameter->name];
				else
					$key .= "_".$parameter->name."=".$this->{$parameter->name};

			}
			reset($this->parameters);
		}

		if ($this->additionalCacheKeys) {
			foreach ($this->additionalCacheKeys as $additionalCacheKey => $value) {
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
		return \Cherrycake\Cache\Cache::buildCacheKey($cacheKeyNamingOptions);
	}

	/**
	 * Checks this request for security problems
	 * @return boolean True if no issues found during checking, false otherwise.
	 */
	function securityCheck(): bool {
		global $e;
		return $e->Security->checkRequest($this);
	}

	/**
	 * @return array Status information
	 */
	function getStatus(): array {
		$r["brief"] = "";
		if ($this->pathComponents) {
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

		if ($this->additionalCacheKeys)
			$r["additionalCacheKeys"] = $this->additionalCacheKeys;

		return $r;
	}
}
