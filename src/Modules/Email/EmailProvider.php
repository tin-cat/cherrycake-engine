<?php

namespace Cherrycake\Modules\Email;

/**
 * Base class for email provider implementations. Intended to be overloaded by a higher level email system implementation class.
 */
class EmailProvider {
	/**
	 * @var array $config Default configuration options
	 */
	protected array $config = [];

	/**
	 * Sets the configuration of the email provider.
	 * @param array $config The email provider parameters
	 */
	function config(array $config) {
		$this->config = $config;
	}

	/**
	 * Gets a configuration value
	 * @param string $key The configuration key
	 */
	function getConfig($key) {
		return isset($this->config[$key]) ? $this->config[$key] : false;
	}
}
