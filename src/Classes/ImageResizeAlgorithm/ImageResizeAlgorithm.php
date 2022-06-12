<?php

namespace Cherrycake\Classes\ImageResizeAlgorithm;

use Exception;

abstract class ImageResizeAlgorithm {
	/**
	 * @param int $outputImageType The imagetype of the output, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @param int $jpegQuality The JPEG quality of the output when using IMAGETYPE_JPEG, ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
	 * @param bool $isInterlaced Whether the output image should be interlaced, when the outputImageType supports it.
	 * @param bool $isCorrectOrientation Whether to rotate the image to bring it to the correct orientation based on EXIF data.
	 */
	public function __construct(
		protected int $outputImageType,
		protected int $jpegQuality = 90,
		protected bool $isInterlaced = true,
		protected bool $isCorrectOrientation = true,
	) {}

	/**
	 * Loads the specified image and returns a GdImage object along its width, height and image type.
	 * It also corrects the image orientation based on EXIF data if needed.
	 * @param string $file The path to the image's local file
	 * @return array An array containing the following keys
	 * - gdImage: A GdImage object that can be handled by GD
	 * - width: The width of the image
	 * - height: The height of the image
	 * - type: The image type, one of the IMAGETYPE_* constants (https://www.php.net/manual/es/image.constants.php)
	 * @throws UnrecognizedFileTypeException if the provided source image is not recognized as a file Type
	 */
	protected function loadImage(
		string $file
	): array {
		if (!$imageSpecs = getimagesize($file))
			throw new UnrecognizedFileTypeException('The provided file could not be recognized as an image');

		list($width, $height, $type) = $imageSpecs;

		switch ($type) {
			case IMAGETYPE_GIF:
				$image = imageCreateFromGif($file);
				break;
			case IMAGETYPE_PNG:
				$image = imageCreateFromPng($file);
				break;
			case IMAGETYPE_JPEG:
				$image = imagecreateFromJpeg($file);
				break;
		}

		if ($this->isCorrectOrientation) {
			if (!function_exists('exif_read_data'))
				throw new Exception('EXIF extension required to retrieve orientation data');
			if ($exif = exif_read_data($file) && isset($exif["Orientation"])) {
				$orientation = $exif["Orientation"];
				if ($orientation == 6 || $orientation == 5)
					$image = imagerotate($image, 270, 0);
				if ($orientation == 3 || $orientation == 4)
					$image = imagerotate($image, 180, 0);
				if ($orientation == 8 || $orientation == 7)
					$image = imagerotate($image, 90, 0);

				if ($orientation == 5 || $orientation == 4 || $orientation == 7)
					imageflip($image, IMG_FLIP_HORIZONTAL);

				if ($orientation == 6 || $orientation == 5 || $orientation == 8 || $orientation == 7) {
					$oldWidth = $width;
					$width = $height;
					$height = $oldWidth;
				}
			}
		}

		return [
			'gdImage' => $image,
			'width' => $width,
			'height' => $height,
			'type' => $type,
		];
	}
}
