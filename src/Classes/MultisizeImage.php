<?php

namespace Cherrycake\Classes;

use Exception;
use Throwable;

/**
 * Class that represents an image that's available in multiple sizes
 */
abstract class MultisizeImage {
	/**
	 * var string $idBasedImageClassName The image class name to use, normally an App-level class that extends the core IdBasedImage class
	 */
	static protected string $idBasedImageClassName;

	/**
	 * var array $sizes The sizes on which the image is available, as an array of ImageResizeAlgorithm objects
	 */
	protected array $sizes;

	/**
	 * An array of Image objects corresponding to each of the sizes available
	 */
	protected $images;

	/**
	 * @return int The width of the original image that generated this MultisizeImage in pixels
	 */
	protected ?int $width = null;

	/**
	 * @var int The height of the original image that generated this MultisizeImage in pixels
	 */
	protected ?int $height = null;

	/**
	 * @var int The type of the original image that generated this MultisizeImage, one of the IMG_* constants (https://www.php.net/manual/en/image.constants.php)
	 */
	protected ?int $type = null;

	/**
	 * @var array the IPTC metadata (https://www.php.net/manual/en/function.iptcparse.php) of the original image that generated this MultisizeImage
	 */
	protected ?array $iptcMetadata = null;

	/**
	 * @var array the EXIF metadata of the original image that generated this MultisizeImage
	 */
	protected ?array $exifMetadata = null;

	/**
	 * @var array The red, green and blue values of the average color of the original image that generated this MultisizeImage, in the form of an hash array with the `red`, `green`, `blue` and `alpha` keys
	 */
	protected ?array $averageColorRgba = null;

	/**
	 * var boolean $isReadIptcMetadata Whether to read IPTC metadada
	 **/
	static public bool $isReadIptcMetadata = false;

	/**
	 * var boolean $isReadExifMetadata Whether to read EXIF metadada
	 **/
	static public bool $isReadExifMetadata = false;

	/**
	 * @param string $sourceImagefilePath The file path of the source image file, from which all sizes will be created
	 * @param string $originalName The original file name, if it's different than $name
	 */
	function __construct(
		string $sourceImageFilePath,
		?string $originalName = null,
	) {
		if (!$originalName)
			$originalName = basename($sourceImageFilePath);

		$this->loadMetadata($sourceImageFilePath);

		// Loop through sizes
		foreach ($this->sizes as $sizeName => $imageResizeAlgorithm) {

			$image = new static::$idBasedImageClassName(
				originalName: $originalName,
			);

			$image->createBaseDir();

			$imageResizeAlgorithm->resize(
				sourceFilePath: $sourceImageFilePath,
				destinationFilePath: $image->getPath(),
			);

			$this->images[$sizeName] = $image;
		}
	}

	/**
	 * Loads all the available metadata for this image
	 */
	public function loadMetadata(
		string $sourceImageFilePath,
	) {
		// Load sizes
		list($this->width, $this->height) = getimagesize($sourceImageFilePath);

		// Load type
		$this->type = exif_imagetype($sourceImageFilePath);

		// Load IPTC metadata
		if (static::$isReadIptcMetadata) {
			getimagesize($sourceImageFilePath, $metadata);
			if (!isset($metadata['APP13']))
				$this->iptcMetadata = [];
			else
				$this->iptcMetadata = iptcparse($metadata['APP13']) ?: [];
		}

		// Load EXIF metadata
		if (
			static::$isReadExifMetadata
			&&
			in_array($this->type, [
				IMAGETYPE_JPEG,
				IMAGETYPE_TIFF_II,
				IMAGETYPE_TIFF_MM,
			])
		) {
			if ($exifMetadata = @exif_read_data($sourceImageFilePath))
				$this->exifMetadata = $exifMetadata;
		}

		// Load average color
		$image = match($this->type) {
			IMAGETYPE_GIF => imageCreateFromGif($sourceImageFilePath),
			IMAGETYPE_PNG => imageCreateFromPng($sourceImageFilePath),
			IMAGETYPE_JPEG => imagecreateFromJpeg($sourceImageFilePath),
			IMAGETYPE_WEBP => imagecreateFromWebp($sourceImageFilePath),
		};

		if (!$image) {
			$this->averageColorRgba = [];
			return [];
		}

		$tempImage = ImageCreateTrueColor(1,1);
		ImageCopyResampled($tempImage, $image, 0, 0, 0, 0, 1, 1, $this->width, $this->height);
		$colorIndex = ImageColorAt($tempImage, 0, 0);

		if ($colorIndex === false) {
			$this->averageColorRgba = [];
			return [];
		}

		$this->averageColorRgba = imagecolorsforindex($tempImage, $colorIndex);
	}

	/**
	 * Returns the Image for the specified size for this MultisizeImage, null if the image doesn't exist
	 * @return Image The Image, an object of the class this::idBasedImageClassName
	 */
	public function getSizeImage(string $sizeName) {
		if (!isset($this->images[$sizeName]))
			return null;
		return $this->images[$sizeName];
	}

	/**
	 * Returnsa all Image objects contained in this multisize image where each key is the size name and each value is an Image object.
	 * If a size Image is not set, it won't be set on the returned array either.
	 * @return array An array of Image objects
	 */
	public function getImages(): array {
		return array_filter(array_map(function($sizeName) {
			return $this->getSizeImage($sizeName);
		}, array_keys($this->sizes)));
	}

	/**
	 * Deletes all the files for this MultisizeImage.
	 * Tries to delete all images even if some image fails deletion.
	 * @return bool Whether all the images for this multisize image were deleted succesfuly. If there weren't any images on the multisize image, also returns true.
	 */
	public function delete(): bool {
		if (!$images = $this->getImages())
			return true;
		$isAllImagesDeleted = true;
		foreach ($images as $image) {
			if (!$image->delete())
				$isAllImagesDeleted = false;
		}
		return $isAllImagesDeleted;
	}

	/**
	 * Retrieves an IPTC metadata value
	 * @param string $key The IPTC metadata key to retrieve
	 * @return string The IPTC metadata value for the specified key, null if it didn't exist
	 */
	public function getIptcMetadata(string $key): ?string {
		return $this->iptcMetadata[$key] ?? null;
	}

	/**
	 * Retrieves an EXIF metadata value
	 * @param string $key The EXIF metadata key to retrieve
	 * @return string The EXIF metadata value for the specified key, null if it didn't exist
	 */
	public function getExifMetadata(string $key): ?string {
		return $this->exifMetadata[$key] ?? null;
	}

	/**
	 * @return array The red, green and blue values of the average color of the image, in the form of an hash array with the `red`, `green`, `blue` and `alpha` keys
	 */
	public function getAverageColorRgba(): array {
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

		if ($isAlpha)
			return sprintf("%02x%02x%02x%02x", $averageColorRgba['red'], $averageColorRgba['green'], $averageColorRgba['blue'], $averageColorRgba['alpha']);
		else
			return sprintf("%02x%02x%02x", $averageColorRgba['red'], $averageColorRgba['green'], $averageColorRgba['blue']);
	}

}
