<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image stored on disk in a controlled path and name structure, based on an automatically generated id.
 */
abstract class IdBasedVideo extends IdBasedFile {
	/**
	 * @return int The width of the image in pixels
	 */
	protected ?int $width = null;

	/**
	 * @var int The height of the image in pixels
	 */
	protected ?int $height = null;

	/**
	 * @var int The type of the image, one of the IMG_* constants (https://www.php.net/manual/en/image.constants.php)
	 */
	protected ?int $type = null;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), [
			'width',
			'height',
			'type',
		]);
	}

	public function copyFromLocalFile(
		string $filePath
	): bool {
		if (!parent::copyFromLocalFile(
			filePath: $filePath,
		))
			return false;
		$this->loadMetadata();
		return true;
	}

	/**
	 * Loads all the available metadata for this image
	 */
	public function loadMetadata() {
		$this->loadSize();
		$this->loadType();
	}

	/**
	 * Loads this image size, width and type by reading the file and analyzing its contents.
	 * If this information was loaded before, it doesn't loads it again.
	 */
	private function loadSize() {
		if (!is_null($this->width) && !is_null($this->height))
			return;
		list($this->width, $this->height) = getimagesize($this->getPath());
	}

	/**
	 * @return int The width of the image in pixels
	 */
	public function getWidth(): int {
		$this->loadSize();
		return $this->width;
	}

	/**
	 * @return int The height of the image in pixels
	 */
	public function getHeight(): int {
		$this->loadSize();
		return $this->height;
	}

	private function loadType() {
		if (!is_null($this->type))
			return;
		$this->type = exif_imagetype($this->getPath());
	}

	/**
	 * @return int The type of the image, one of the IMG_* constants (https://www.php.net/manual/en/image.constants.php)
	 */
	public function getType(): int {
		$this->loadType();
		return $this->type;
	}

	public function getMimeType(): string {
		$this->loadType();
		return image_type_to_mime_type(exif_imagetype($this->type));
	}
}
