<?php

namespace Cherrycake\Modules\ObjectStorage;

class ObjectStorageProviderS3 extends ObjectStorageProvider {
	private $s3Client = null;

	protected function connect() {
		if (!is_null($this->s3Client))
			return true;

		$this->s3Client = Aws\S3\S3Client::factory([
			"version" => "2006-03-01",
			"region" => $this->config["region"],
			"signature" => "v4",
			"credentials" => [
				"key" => $this->config["credentials"]["AccessKeyId"],
				"secret" => $this->config["credentials"]["SecretAccessKey"]
			],
		]);

		return $this->s3Client ? true : false;
	}

	public function put($p) {
		$this->requireConnection();
		if (!file_exists($p["originFile"]))
			throw new Exception("File {$p['originFile']} does not exist");
		$remoteKey = $this->buildRemoteKey($p["destinationFileName"]);
		$this->s3Client->putObject([
			"Bucket" => $this->config["bucket"],
			"SourceFile" => $p["originFile"],
			"Key" => $remoteKey,
		]);
		return new ObjectStorageObjectS3([
			"providerName" => $this->name,
			"region" => $this->config["region"],
			"bucket" => $this->config["bucket"],
			"remoteKey" => $remoteKey,
			"additionalData" => $this->buildAdditionalData($p)
		]);
	}

	public function delete($p) {
		$this->requireConnection();
		$this->s3Client->deleteObject([
			"Bucket" => $p["bucket"] ?: $this->config["bucket"],
			"Key" => $p["remoteKey"]
		]);
	}

	public function exists($p) {
		$this->requireConnection();
		return $this->s3Client->doesObjectExist(
			$p["bucket"] ?: $this->config["bucket"],
			$p["remoteKey"]
		);
	}
}
