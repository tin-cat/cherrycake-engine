<?php

namespace Cherrycake\Actions;

/**
 * Request
 *
 * A class that represents a path component of a Request
 *
 * @package Cherrycake
 * @category Classes
 */
class RequestPathComponent {
	private mixed $value = false;

	function __construct(
		public int $type,
		public string $string,
		public string $name = '',
		private array $securityRules = [],
		private array $filters = [],
	) {}

	/**
	 * Checks whether the given string matches this RequestPathComponent syntax, to know if the given string is or could be representing this RequestPathComponent
	 * @param string $string The string to check against to
	 * @return bool Returns true if the given $string is or could be representing this RequestPathComponent
	 */
	function isMatchesString(string $string): bool {
		switch ($this->type) {
			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED:
				return (strcasecmp($string, $this->string) == 0 ? true : false);
				break;

			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
				return true;
				break;

			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
				return is_numeric($string);
				break;
		}
	}

	/**
	 * @return string The name of this RequestPathComponent's type, mainly for debugging purposes
	 */
	function getTypeName(): string {
		switch ($this->type) {
			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED:
				return "Fixed";
				break;
			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING:
				return "String";
				break;
			case \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC:
				return "Numeric";
				break;
		}
	}

	/**
	 * @return string Returns the value passed for this path component, if its type is either \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING or \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC
	 */
	function getValue(): mixed {
		return $this->value;
	}

	/**
	 * Sets the value for this path component. Intented to apply only for \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_STRING and \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_VARIABLE_NUMERIC types
	 * @param mixed $value The value for this path component
	 */
	function setValue(mixed $value) {
		global $e;
		$this->value = $e->Security->filterValue($value, $this->filters);
	}

	/**
	 * Checks this path component's value against its configured security rules (and/or the Security defaulted rules)
	 * @return Result A Result object, like Security::checkValue
	 */
	function checkValueSecurity(): Result {
		global $e;
		return $e->Security->checkValue($this->getValue(), $this->securityRules);
	}

	/**
	 * @return array Status information
	 */
	function getStatus(): array {
		$r["brief"] = ($this->type == \Cherrycake\REQUEST_PATH_COMPONENT_TYPE_FIXED ? $this->string : "[".$this->getTypeName()."]");
		$r["name"] = $this->name ?? "unnamed";
		$r["value"] = $this->getValue();
		$r["type"] = $this->getTypeName();

		if ($this->string)
			$r["string"] = $this->string;
		if ($this->securityRules) {
			foreach ($this->securityRules as $securityRule)
				$r["securityRules"][] = $securityRule;
			reset($this->securityRules);
		}
		if ($this->filters) {
			foreach ($this->filters as $filter)
				$r["filters"][] = $filter;
			reset($this->filters);
		}
		return $r;
	}
}
