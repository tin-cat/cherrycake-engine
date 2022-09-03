<?php

namespace Cherrycake\Modules\Actions;

use Cherrycake\Classes\Engine;

/**
 * A class that represents a parameter passed to a Request via Get or Post
 */
class RequestParameter {

	/**
	 * @param string $forItemClassName If the request parameter receives data that will be stored in an Item object, the name of that Item class. This mechanism allows for the automatic creation of security rules for this action that match the Item's fields specification.
	 */
	function __construct(
		public int $type,
		public string $name,
		public mixed $value = null,
		private array $securityRules = [],
		private array $filters = [],
		private string $forItemClassName = '',
	) {}

	/**
	 * retrieveValue
	 */
	function retrieveValue() {
		switch ($this->type) {
			case \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_GET:
			case \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_CLI:
				if (isset($_GET[$this->name]))
					$this->setValue($_GET[$this->name]);
				break;
			case \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_POST:
				if (isset($_POST[$this->name]))
					$this->setValue($_POST[$this->name]);
				break;
			case \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILE:
				if (isset($_FILES[$this->name]))
					$this->setValue($_FILES[$this->name]);
				break;
			case \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILES:
				if (isset($_FILES))
					$this->setValue($_FILES);
				break;
		}
	}

	/**
	 * Should be called only after calling retrieveValue
	 * @return Boolean Whether this parameter has been received or not
	 */
	function isReceived() {
		return !is_null($this->value);
	}

	/**
	 * @return mixed Returns the value received for this parameter after applying the proper filters, null if the parameter was not received
	 */
	function getValue() {
		if (!$this->isReceived())
			return null;
		return
			$this->type == \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILE
			||
			$this->type == \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILES
			?
			$this->value
			:
			Engine::e()->Security->filterValue($this->value, $this->filters);
	}

	/**
	 * Sets the value for this parameter
	 * @param mixed $value The value
	 */
	function setValue($value) {
		$this->value = $value;
	}

	/**
	 * @return array The security rules to apply when receiving values for this request parameter
	 */
	private function getSecurityRules(): array {
		$securityRules = $this->securityRules;

		// If this request parameter is for a value that matches a field in an Item class, add the field's specific security rules
		if ($this->forItemClassName) {
			$forItemClassName = $this->forItemClassName;
			$securityRules = array_merge($securityRules, $forItemClassName::getSecurityRules($this->name));
		}

		return $securityRules;
	}

	/**
	 * checkValueSecurity
	 *
	 * Checks this parameter's value against its configured security rules (and/or the Security defaulted rules)
	 *
	 * @return Result A Result object, like Security::checkValue
	 */
	function checkValueSecurity() {
		return
			match ($this->type) {
				\Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILE => Engine::e()->Security->checkFile($this->value, $this->getSecurityRules()),
				\Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILES => Engine::e()->Security->checkFiles($this->value, $this->getSecurityRules()),
				default => Engine::e()->Security->checkValue($this->value, $this->getSecurityRules())
			};
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		$r["brief"] = $this->name."=".(is_array($this->value) ? json_encode($this->value) : $this->value ?? "none");
		$r["name"] = $this->name ?? "unnamed";
		$r["value"] = $this->value ?? "none";
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
