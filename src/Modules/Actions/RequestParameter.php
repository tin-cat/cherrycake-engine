<?php

namespace Cherrycake\Modules\Actions;

use Cherrycake\Engine;

/**
 * A class that represents a parameter passed to a Request via Get or Post
 */
class RequestParameter {
	function __construct(
		public int $type,
		public string $name,
		public mixed $value = null,
		private array $securityRules = [],
		private array $filters = []
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
	 * checkValueSecurity
	 *
	 * Checks this parameter's value against its configured security rules (and/or the Security defaulted rules)
	 *
	 * @return Result A Result object, like Security::checkValue
	 */
	function checkValueSecurity() {
		return
			$this->type == \Cherrycake\Modules\Actions\Request::PARAMETER_TYPE_FILE
			?
			Engine::e()->Security->checkFile($this->value, $this->securityRules)
			:
			Engine::e()->Security->checkValue($this->value, $this->securityRules);
	}

	/**
	 * @return array Status information
	 */
	function getStatus() {
		$r["brief"] = $this->name."=".($this->value ?? "none");
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