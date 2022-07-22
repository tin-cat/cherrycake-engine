<?php

namespace Cherrycake\Modules\ObjectStorage;

/**
 * Manages object storage providers.
 * It takes configuration from the App-layer configuration file.
 *
 * Configuration example for ObjectStorage.config.php:
 * <code>
 * $ObjectStorageConfig = [
 * 	'providers' => [
 * 		'main' => [
 * 			'providerClassName' => 'ObjectStorageProviderAwsS3',
 * 			'config' => [
 * 				'region' => 'eu-west-3',
 * 				'bucket' => 'static-devel.rawlock.com',
 * 				'publicEndpoint' => 'https://static-devel.rawlock.com',
 * 				'credentials' => [
 * 					'AccessKeyId' => '',
 * 					'SecretAccessKey' => ''
 * 				],
 * 				'folder' => ''
 * 			]
 * 		]
 * 	]
 * ];
 * </code>
 */
class ObjectStorage extends \Cherrycake\Classes\Module {
	const SYSTEM_AWS_S3 = 0;

	private static $instance;

	private $providersConfig;
	private $providers;

	private $systemClassNames = [
		self::SYSTEM_AWS_S3 => "ObjectStorageProviderAwsS3"
	];

	/**
	 * Initializes the module and loads the providers
	 * @return boolean Whether the module has been initted ok
	 */
	function init(): bool {
		if (!parent::init())
			return false;

		// Sets up providers
		if (is_array($providers = $this->getConfig("providers")))
			foreach ($providers as $key => $provider)
				$this->addProvider($key, $provider["providerClassName"], $provider["config"]);

		return true;
	}

	/**
	 * Adds a provider
	 * @param string $key The key to later access the provider
	 * @param string $providerClassName The provider class name
	 * @param array $config The configuration for the provider
	 */
	function addProvider(string $key, string $providerClassName, array $config) {
		$providerClassName = '\\Cherrycake\\Modules\\ObjectStorage\\'.$providerClassName;
		$this->$key = new $providerClassName(
			providerName: $key,
			config: $config
		);
	}

	/**
	 * @param string $providerName
	 * @return ObjectStorageProvider
	 * @throws ObjectStorageException
	 */
	public function getProvider($providerName) {
		if (!isset($this->$providerName))
			throw new ObjectStorageException("Object storage provider $providerName not found");
		return $this->$providerName;
	}
}
