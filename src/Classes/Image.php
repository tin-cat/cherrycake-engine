<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image stored on disk in a controlled path and name structure, based on an automatically generated id.
 */
abstract class Image extends File {
	/**
	 * @return int The width of the image in pixels
	 */
	private ?int $width = null;

	/**
	 * @var int The height of the image in pixels
	 */
	private ?int $height = null;

	/**
	 * @var int The type of the image, one of the IMG_* constants (https://www.php.net/manual/en/image.constants.php)
	 */
	private ?int $type = null;

	/**
	 * @var array The image IPTC metadata (https://www.php.net/manual/en/function.iptcparse.php)
	 */
	private ?array $iptcMetadata = null;

	/**
	 * @var array The image EXIF metadata
	 */
	private ?array $exifMetadata = null;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), [
			'width',
			'height',
			'type'
		]);
	}

	/**
	 * Loads this image size, width and type by reading the file and analyzing its contents.
	 * If this information was loaded before, it doesn't loads it again.
	 */
	private function loadSizes() {
		if (!is_null($this->width) && !is_null($this->height))
			return;
		list($this->width, $this->height, $this->type) = getimagesize($this->getPath());
	}

	/**
	 * @return int The width of the image in pixels
	 */
	public function getWidth(): int {
		$this->loadSizes();
		return $this->width;
	}

	/**
	 * @return int The height of the image in pixels
	 */
	public function getHeight(): int {
		$this->loadSizes();
		return $this->height;
	}

	/**
	 * @return float The number of megapixels of this image
	 */
	public function getMegapixels(): float {
		return $this->getWidth() * $this->getheight() / 1000000;
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

	/**
	 * @return string The mime type of the image
	 */
	public function getMimeType(): string {
		$this->loadType();
		return exif_imagetype($this->type);
	}

	/**
	 * Loads this image IPTC metadata (https://www.php.net/manual/en/function.iptcparse.php) by analyzing its contents.
	 * If this information was loaded before, it doesn't loads it again.
	 */
	private function loadIptcMetadata() {
		if (!is_null($this->iptcMetadata))
			return;
		getimagesize($this->getPath(), $metadata);
		if (!isset($metadata['APP13']))
			$this->iptcMetadata = [];
		else
			$this->iptcMetadata = iptcparse($metadata['APP13']);
	}

	/**
	 * Retrieves an IPTC metadata value
	 * @param string $key The IPTC metadata key to retrieve
	 * @return string The IPTC metadata value for the specified key, null if it didn't exist
	 */
	public function getIptcMetadata(string $key): ?string {
		$this->loadIptcMetadata();
		return $this->iptcMetadata[$key] ?? null;
	}

	/**
	 * Loads this image EXIF metadata by analyzing its contents.
	 * If this information was loaded before, it doesn't loads it again.
	 */
	private function loadExifMetadata() {
		if (!is_null($this->exifMetadata))
			return;
		$this->exifMetadata = exif_read_data($this->getPath());
	}

	/**
	 * Retrieves an IPTC metadata value
	 * @param string $key The IPTC metadata key to retrieve
	 * @return string The IPTC metadata value for the specified key, null if it didn't exist
	 */
	public function getExifMetadata(string $key): ?string {
		$this->loadExifMetadata();
		return $this->exifMetadata[$key] ?? null;
	}
}
