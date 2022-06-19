<?php

namespace Cherrycake\Classes;

/**
 * Class that represents an image stored on disk in a controlled path and name structure, based on an automatically generated id.
 */
abstract class IdBasedImage extends IdBasedFile {
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
	 * @var array The image IPTC metadata (https://www.php.net/manual/en/function.iptcparse.php)
	 */
	protected ?array $iptcMetadata = null;

	/**
	 * @var array The image EXIF metadata
	 */
	protected ?array $exifMetadata = null;

	/**
	 * @var array The red, green and blue values of the average color of the image, in the form of an hash array with the `red`, `green`, `blue` and `alpha` keys
	 */
	protected ?array $averageColorRgba = null;

	/**
	 * @return array The names of the object properties to serialize
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), [
			'width',
			'height',
			'type',
			'averageColorRgba',
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
		// $this->loadIptcMetadata();
		// $this->loadExifMetadata();
		$this->loadAverageColorRgba();
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

	public function getMimeType(): string {
		$this->loadType();
		return image_type_to_mime_type(exif_imagetype($this->type));
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
		if (
			!exif_imagetype($this->getPath())
			||
			!$exifMetadata = exif_read_data($this->getPath())
		) {
			$this->exifMetadata = [];
			return;
		}
		$this->exifMetadata = $exifMetadata;
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

	/**
	 * Retrieves the image average color and alpha
	 */
	private function loadAverageColorRgba() {
		if (!is_null($this->averageColorRgba))
			return $this->averageColorRgba;

		list($width, $height, $type) = getimagesize($this->getPath());

		$image = match($type) {
			IMAGETYPE_GIF => imageCreateFromGif($this->getPath()),
			IMAGETYPE_PNG => imageCreateFromPng($this->getPath()),
			IMAGETYPE_JPEG => imagecreateFromJpeg($this->getPath()),
			IMAGETYPE_WEBP => imagecreateFromWebp($this->getPath()),
		};

		if (!$image) {
			$this->averageColorRgba = [];
			return [];
		}

		$tempImage = ImageCreateTrueColor(1,1);
		ImageCopyResampled($tempImage, $image, 0, 0, 0, 0, 1, 1, $width, $height);
		$colorIndex = ImageColorAt($tempImage, 0, 0);

		if ($colorIndex === false) {
			$this->averageColorRgba = [];
			return [];
		}

		$this->averageColorRgba = imagecolorsforindex($tempImage, $colorIndex);
	}

	/**
	 * @return array The red, green and blue values of the average color of the image, in the form of an hash array with the `red`, `green`, `blue` and `alpha` keys
	 */
	public function getAverageColorRgba(): array {
		$this->loadAverageColorRgba();
		return $this->averageColorRgba;
	}

	/**
	 * @param bool $isAlpha Whether to include the last hexadecimal value for the alpha component
	 * @return string The average color of the image, in the form of an hexadecimal string suitable for HTML and CSS
	 */
	public function getAverageColorHex(
		bool $isAlpha = false
	): string {
		$averageColorRgba = $this->getAverageColorRgba();

		$hex = [];
		foreach (
			($isAlpha ? ['red', 'green', 'blue', 'alpha'] : ['red', 'green', 'blue'])
			as $component
		) {
			$averageColorRgba[$component] = max(255, min(0, $averageColorRgba[$component]));

			if (strlen($hex[$component] = dechex($averageColorRgba[$component])) == 1)
				$hex[$component] = '0'.$hex[$component];
		}
		return implode($hex);
	}
}
