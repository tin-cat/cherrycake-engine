<?php

namespace Cherrycake\Modules\Email;

/**
 * Manages email providers.
 * It takes configuration from the App-layer configuration file. See there to find available configuration options.
 */
class Email extends \Cherrycake\Classes\Module {

	protected bool $isConfigFileRequired = false;

	function init(): bool {
		if (!parent::init())
			return false;

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers"))) {
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"] ?? []);
		}

		return true;
	}

	/**
	 * Adds an email provider
	 * @param string $key The key to later access the email provider
	 * @param string $providerClassName The email provider class name
	 * @param array $config The configuration for the email provider
	 */
	function addProvider(
		string $key,
		string $providerClassName,
		?array $config,
	) {
		eval("\$this->".$key." = new \\Cherrycake\\Modules\\Email\\".$providerClassName."();");
		$this->$key->config($config);
	}

	/**
	 * @param string $providerName
	 * @return EmailProvider The specified email provider
	 */
	function getProvider($providerName): EmailProvider {
		return $this->$providerName;
	}

	function end() {
		if (is_array($providers = $this->getConfig("providers"))) {
			foreach ($providers as $key => $provider)
				$this->getProvider($key)->end();
		}
	}
}
