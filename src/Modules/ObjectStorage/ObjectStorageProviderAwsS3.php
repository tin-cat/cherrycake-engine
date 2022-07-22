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
				'version' => '2006-03-01',
				'region' => $this->config['region'],
				'signature' => 'v4',
				'credentials' => [
					'key' => $this->config['credentials']['AccessKeyId'],
					'secret' => $this->config['credentials']['SecretAccessKey']
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
				'{bucket}',
				'{region}',
			],
			[
				$this->config['bucket'],
				$this->config['region'],
			],
			$this->config['publicEndpoint']
		).
		($this->config['folder'] ? '/'.$this->config['folder'] : null);
	}

	private function getFinalId($id): string {
		return
			($this->config['folder'] ? $this->config['folder'].'/' : null).
			$id;
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
				'Bucket' => $this->config['bucket'],
				'SourceFile' => $originFilePath,
				'Key' => $this->getFinalId($id),
			]);
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}

		return true;
	}

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
				'Bucket' => $this->config['bucket'],
				'Key' => $this->getFinalId($id)
			]);
			// We do not check $result['DeleteMarker'] to see if the object was indeed deleted because it's not a reliable way of telling if the object has been effectively removed from S3.
			// this might be related to the way buckets with versioning enabled work (https://docs.aws.amazon.com/AmazonS3/latest/userguide/DeleteMarker.html)
			return true;
		} catch (S3Exception $e) {
			throw new ObjectStorageException("Could not delete object $id: ".$e->getAwsErrorMessage());
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function isExists(
		string $id
	): bool {
		$this->requireConnection();
		try {
			return $this->s3Client->doesObjectExist(
				$this->config['bucket'],
				$this->getFinalId($id)
			);
		} catch (S3Exception $e) {
			throw new ObjectStorageException("Could not check existence of object $id: ".$e->getAwsErrorMessage());
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}

	public function getSize(
		string $id,
	): int {
		$this->requireConnection();
		try {
			$objectData = $this->s3Client->headObject([
				'Bucket' => $this->config['bucket'],
				'Key' => $this->getFinalId($id)
			]);
			return $objectData['ContentLength'];
		} catch (S3Exception $e) {
			throw new ObjectStorageException("Could not get size of object $id: ".$e->getAwsErrorMessage());
		} catch (Exception $e) {
			throw new ObjectStorageException($e);
		}
	}
}
