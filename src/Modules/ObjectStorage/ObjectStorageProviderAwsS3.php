<?php

namespace Cherrycake\Modules\ObjectStorage;

use Exception;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class ObjectStorageProviderAwsS3 extends ObjectStorageProvider {
	private $s3Client = null;

	protected function connect(): bool {
		if (!is_null($this->s3Client))
			return true;

		try {
			$this->s3Client = S3Client::factory([
				"version" => "2006-03-01",
				"region" => $this->config["region"],
				"signature" => "v4",
				"credentials" => [
					"key" => $this->config["credentials"]["AccessKeyId"],
					"secret" => $this->config["credentials"]["SecretAccessKey"]
				],
			]);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}

		return $this->s3Client ? true : false;
	}

	public function getPublicEndpointUrl(): string {
		return str_replace(
			[
				"{bucket}",
				"{region}"
			],
			[
				$this->config["bucket"],
				$this->config["region"]
			],
			$this->config["publicEndpoint"]
		);
	}

	public function put(
		string $originFilePath,
		string $id,
	): bool {
		$this->requireConnection();

		if (!file_exists($originFilePath))
			throw new ObjectStorageException("File $originFilePath does not exist");

		try {
			$this->s3Client->putObject([
				"Bucket" => $this->config["bucket"],
				"SourceFile" => $originFilePath,
				"Key" => $id,
			]);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}

		return true;
	}

	/**
	 * Gets an object from the object storage
	 * @param string $id The object id on the object storage
	 * @return ObjectStorageObject The ObjectStorageObject object in the
	 * @throws ObjectStorageException
	 */
	public function get(
		string $id
	): ObjectStorageObject {
		return new ObjectStorageObjectS3(
			providerName: $this->providerName,
			id: $id,
		);
	}

	public function delete(
		string $id
	): bool {
		$this->requireConnection();
		try {
			$result = $this->s3Client->deleteObject([
				"Bucket" => $this->config["bucket"],
				"Key" => $id
			]);
			if ($result['DeleteMarker'])
				return true;
			return false;
		} catch (S3Exception $e) {
			throw new ObjectStorageException("Could not delete object $id: ".$e->getAwsErrorMessage());
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function exists(
		string $id
	): bool {
		$this->requireConnection();
		try {
			return $this->s3Client->doesObjectExist(
				$this->config["bucket"],
				$id
			);
		} catch (S3Exception $e) {
			throw new ObjectStorageException("Could not check existence of object $id: ".$e->getAwsErrorMessage());
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}
}
